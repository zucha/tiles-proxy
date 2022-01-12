<?php
namespace zucha\tilesProxy;

/**
 * Tiles controller
 */
class Controller extends \yii\web\Controller 
{
    /**
     * {@inheritDoc}
     * @see \yii\base\Controller::actions()
     */
    public function actions ()
    {
        return [
            'index' => [
                'class' => Proxy::class
            ],
            'error' => [
                'class' => \yii\web\ErrorAction::class,
                'view' => '@app/views/site/error.php'
            ]
        ];
    }
}
