<?php

namespace Xitara\Nexus\Components;

use Cms\Classes\ComponentBase;

class FontAwsome extends ComponentBase
{
    private static $defaultSprite;
    private $faVersion = '6.1.1';

    public function componentDetails()
    {
        return [
            'name' => 'xitara.nexus::fontawsome.name',
            'description' => 'xitara.nexus::fontawsome.description',
        ];
    }

    public function onRun()
    {
        self::$defaultSprite = $this->property('default_sprite');

        $this->addCss(plugins_url('xitara/nexus/assets/css/fontawesome.css'), $this->faVersion);

        if ($this->property('regular') || self::getDefaultSprite() == 'regular') {
            $this->addCss(plugins_url('xitara/nexus/assets/css/regular.css'), $this->faVersion);
        }

        if ($this->property('solid') || self::getDefaultSprite() == 'solid') {
            $this->addCss(plugins_url('xitara/nexus/assets/css/solid.css'), $this->faVersion);
        }

        if ($this->property('brands') || self::getDefaultSprite() == 'brands') {
            $this->addCss(plugins_url('xitara/nexus/assets/css/brands.css'), $this->faVersion);
        }
    }

    public function defineProperties()
    {
        return [
            'regular' => [
                'title' => 'xitara.nexus::fontawsome.regular.label',
                'description' => 'xitara.nexus::fontawsome.regular.description',
                'default' => 0,
                'type' => 'checkbox',
            ],
            'solid' => [
                'title' => 'xitara.nexus::fontawsome.solid.label',
                'description' => 'xitara.nexus::fontawsome.solid.description',
                'default' => 0,
                'type' => 'checkbox',
            ],
            'brands' => [
                'title' => 'xitara.nexus::fontawsome.brands.label',
                'description' => 'xitara.nexus::fontawsome.brands.description',
                'default' => 0,
                'type' => 'checkbox',
            ],
            'default_sprite' => [
                'title' => 'xitara.nexus::fontawsome.default_sprite.label',
                'description' => 'xitara.nexus::fontawsome.default_sprite.description',
                'default' => 'regular',
                'type' => 'dropdown',
                'options' => [
                    'regular' => 'Regular',
                    'solid' => 'Solid',
                    'brands' => 'Brands',
                ],
            ],
        ];
    }

    public static function getDefaultSprite()
    {
        return self::$defaultSprite;
    }
}
