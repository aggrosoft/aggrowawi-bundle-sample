<?php

/**
 * A service is the best way to hook into events and to add custom controller
 * logic. You can add as many services as you like, be sure to prefix your
 * route and service names to avoid naming clashes with the autoloader
 */

namespace Aggrosoft\DemoBundleBundle\Service;

use JMS\DiExtraBundle\Annotation as DI;
use JMS\DiExtraBundle\Annotation\Inject;
use JMS\DiExtraBundle\Annotation\InjectParams;
use Aggrosoft\WAWIBundle\Service\PluggableService;
use Symfony\Component\HttpFoundation\Request;

// Look at this class to see all events available
use Aggrosoft\WAWIBundle\Event\WAWIEvents;

/**
 * The following annotation is split into 2 groups
 * 
 * The di\service part registers this as a service and is mandatory for all
 * service classes
 * 
 * The Route annotaiton will allow this service to define it's own routes,
 * meaning you can add own screens and controller logic here. E.g. add own
 * forms and react to their input
 */

/**
* We are extending PluggableService, if we do this you can generate a 
* settings screen by overriding $pluginSettings in this class.
*/


/**
 * @DI\Service("demobundle.service")
 * @Route(service="demobundle.service")
 */
class DemoBundleService extends PluggableService {

    
    /**
     * the following is the dependency injector part, please read the symfony
     * docs at https://symfony.com/doc/current/components/dependency_injection.html
     * and especially http://jmsyst.com/bundles/JMSDiExtraBundle/master/annotations 
     * as we are using the annotation style here for easy self contained configuration
     */
    
    protected $plugin;
    protected $pluginService;
    protected $importer;
    protected $dispatcher;
    protected $uploadService;
    protected $em;
    protected $userid;
    protected $router;
    protected $webshophelper;
    protected $appservice;
    protected $renderer;
    
    /**
     * @InjectParams({
     *     "em" = @Inject("doctrine.orm.entity_manager"),
     *     "warehouseService" = @Inject("warehouse.service"),
     *     "dispatcher" = @Inject("event_dispatcher"),
     * 	   "appservice" = @Inject("appservice"),
     *     "pluginService" = @Inject("plugin.service"),
     *     "orderService" = @Inject("orderservice"),
     *     "customerService" = @Inject("customer.service"),
     *     "offerService" = @Inject("offer.service"),
     *     "router" = @Inject("router"),
     *     "buzz" = @Inject("buzz"),
     *     "renderer" = @Inject("twig"),
     * })
     */
    public function __construct($em, $warehouseService, $dispatcher, $appservice, $pluginService, $orderService, $customerService, $offerService, $router, $buzz, $renderer) {
        $this->em = $em;
        $this->warehouseService = $warehouseService;
        $this->dispatcher = $dispatcher;
        $this->appservice = $appservice;
        $this->pluginService = $pluginService;
        $this->orderService = $orderService;
        $this->customerService = $customerService;
        $this->offerService = $offerService;
        $this->pluginName = "fachwelt";
        $this->router = $router;
        $this->buzz = $buzz;
        $this->renderer = $renderer;
    }
    
    /**
     * As mentioned above you can allow parameters to be set for your plugin/service
     * just define them here. Be sure to keep the sorting constant if you are adding
     * parameters.
     */
    
    protected $pluginSettings = array(
        array("name" => "myparam", "displayName" => "My great param", "fieldType" => "text","sort"=>10),
        array("name" => "mypass", "displayName" => "My secret pass", "fieldType" => "password","sort"=>20)
    );

    /**
     * The following register the plugin, please just copy this or adapt as needed
     */
    
    /**
     * @DI\Observe("load.plugin.settings", priority = 255)
     */
    public function onLoadPluginSettingsEvent($event) {
        if ($event->getPlugin()->getIdent() == $this->pluginName)
            parent::onLoadPluginSettingsEvent($event);
    }

    /**
     * @DI\Observe("init.plugins", priority = 255)
     */
    public function onPluginInit($event) {
        $this->checkForPluginInstalled($this->pluginName, 'demo');
    }

    //Current version of your plugin    
    public function getRevision()
    {
        return 1;
    }
    
    //Will be called if the version/revision changes
    public function updatePlugin()
    {
    }
    
    //A description to be shown in the plugin settings, can hold HTML
    public function getDescription()
    {
        return null;
    }
    
    /**
     * Starting from here we are adding custom logic, let's react to the 
     * new.order.imported event
     */
    
    /**
     * @DI\Observe("new.order.imported", priority = 255)
     */
    public function onNewOrderImported($event) {
        $this->handleNewOrderImported($event);
    }

    protected function handleNewOrderImported($event){

        $data = $event->getRawData();
        $order = $event->getOrder();
        $offer = $order->getOffer();
        
        //$data is the raw server connector data,
        //$order and $offer will be the newly created entities 
    }

    /**
     * Now let's add a custom csv export below the order grid and handle that using 
     * a custom route. Look at the twig template for the users frontend part
     */
        
    /**
     * @DI\Observe("get.custom.exports", priority = 255)
     */
    public function onGetCustomExports($event) {
        if ($event->getEntityType() == "Aggrosoft\WAWIBundle\Entity\Order") {
            $template = $this->appservice->getTemplatePath("AggrosoftDemoBundle:Exporter:demo_export_orders_form.html.twig");
            $html = $this->renderer->render($template);
            $event->addExport($html);
        }
    }

    /**
     * When the user clicks export in the form the following route will be 
     * called. Be sure to have a unique name and route. See 
     * http://symfony.com/doc/current/routing.html
     */
    
    /**
     * @Route("/demoexportordersascsv", name="demo_export_orders_as_csv")
     */
    public function exportOrdersAsCSV(Request $request) {
        $from = $request->request->get('from');
        $to = $request->request->get('to');

        if (!$from)
            $fromDate = '0000-00-00 00:00:00';
        else {
            $arr = date_parse($from);
            $fromDate = date('Y-m-d H:i:s', mktime(0, 0, 0, $arr["month"], $arr["day"], $arr["year"]));
        }
        if (!$to)
            $toDate = date('Y-m-d H:i:s');
        else {
            $arr = date_parse($to);
            $toDate = date('Y-m-d H:i:s', mktime(23, 59, 59, $arr["month"], $arr["day"], $arr["year"]));
        }
        
        $qb = $this->em->createQueryBuilder();
        $qb->select('i.sku, i.productTitle, SUM(i.quantity), i.price, i.vat, SUM(i.price)')
            ->from('AggrosoftWAWIBundle:Order', 'o')
            ->join('o.offer', 'of')
            ->join('of.items', 'i')
            ->where( $qb->expr()->between('o.created', "'" . $fromDate . "'", "'" . $toDate . "'") )
            ->groupBy('i.product');

        
        $data = $qb->getQuery()->getArrayResult();

        array_unshift($data, array('No.', 'Name', 'Amount', 'Sales Price', 'Tax', 'Brut. Sum'));

        $filename = "fachweltexport-$fromDate-$toDate";

        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename={$filename}.csv");
        header("Pragma: no-cache");
        header("Expires: 0");

        $outputBuffer = fopen("php://output", 'w');
        foreach($data as $val) {
            fputcsv($outputBuffer, $val);
        }
        fclose($outputBuffer);

        exit();
    }
    
}
