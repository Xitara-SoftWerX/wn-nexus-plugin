<?php

namespace Xitara\Nexus\Components;

use Cms\Classes\ComponentBase;

class ProgressiveWebApp extends ComponentBase
{
    /**
     * Gets the details for the component
     */
    public function componentDetails()
    {
        return [
            'name' => 'ProgressiveWebApp Component',
            'description' => 'No description provided yet...',
        ];
    }

    public function onRun()
    {
        $this->addJs('assets/js/pwa.js');
        $this->addJs('assets/js/sw.js');
        $this->addJs('assets/js/vendor.js');
        // $this->addCss('assets/css/pwa.css');
    }
}
