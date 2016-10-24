# aggrowawi-bundle-sample
A simple example to create your own extension for AggroWAWI

## Getting started

1. Download the *.zip or clone the repository
2. Copy the contents into your AggroWAWI installation
3. Register the bundle in the file `app/bundles/bundles.inc.php`

    ```php
    <?php

    function get_bundles()
    {
        return array(
                    new Aggrosoft\ShirtnetworkBundle\AggrosoftShirtnetworkBundle(),
                    //This is the new entry, adapt to your needs
                    new Aggrosoft\DemoBundle\AggrosoftDemoBundle()
            );
    }
    ```

4. Add routing info if you need to have custom controller routes to `src/Aggrosoft/WAWIBundle/Resources/config/routing_bundles.yml`

    ```yaml
    #...
    demo:
        resource: "@AggrosoftDemoBundle/Resources/config/routing.yml"
    ```

5. Clear the symfony cache
6. Try your bundle, look at the comments in `src/Aggrosoft/DemoBundle/Service/DemoService.php` for more information.

## Warning

This is just a quick and dirty help to get you started, we will add more documentation ASAP!