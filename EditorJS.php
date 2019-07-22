<?php

namespace kodia912\yii2editorjs;

use Yii;
use yii\web\View;
use yii\helpers\Json;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\InputWidget;

class EditorJS extends InputWidget
{
    public $editorOptions = [];

    public $containerOptions = [];

    public $extraPlugins = [];

    public $defaultPlugins = [
        'header' => [
            'var' => 'header: Header',
            'file' => 'header/plugin.js'
        ],
        'image' => [
            'var' => 'image: ImageTool',
            'file' => 'image/plugin.js'
        ],
        'embed' => [
            'var' => 'embed: Embed',
            'file' => 'embed/embed.js'
        ],
        'delimiter' => [
            'var' => 'delimiter: Delimiter',
            'file' => 'delimiter/plugin.js'
        ],
        'list' => [
            'var' => 'list: List',
            'file' => 'list/plugin.js'
        ],
        'quote' => [
            'var' => 'quote: Quote',
            'file' => 'quote/plugin.js'
        ],
        'table' => [
            'var' => 'table: Table',
            'file' => 'table/plugin.js'
        ],
        'warning' => [
            'var' => 'warning: Warning',
            'file' => 'warning/plugin.js'
        ]
    ];

    private $fullPlugins = [];

    public $tools = [];



    public function init()
    {
        parent::init();

        foreach ( $this->defaultPlugins as $key => $item){
            $this->defaultPlugins[$key]['file'] = __DIR__.'/assets/editorjs/plugins/'.$item['file'];
        }

        if( count($this->extraPlugins) ){
            $this->fullPlugins = array_merge($this->defaultPlugins, $this->extraPlugins);
        } else {
            $this->fullPlugins = $this->defaultPlugins;
        }

        if (!isset($this->containerOptions['id'])) {
            $this->containerOptions['id'] = $this->getRandomID();
        }

    }

    private function getRandomID($length = 20){
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return 'id'.$randomString;
    }


    public function run()
    {
        AssetBundle::register($this->getView());

//        $this->addExtraPlugins();
        $this->addPluginsJs();

        echo Html::beginTag('div', $this->containerOptions);

        if ($this->hasModel()) {
            echo Html::activeTextarea($this->model, $this->attribute, array_merge($this->options, ['style' => 'display: block;', 'class' => $this->containerOptions['id']]));
        } else {
            echo Html::textarea($this->name, $this->value, $this->options);
        }

        echo Html::endTag('div');


        $editorJs = $this->getEditorJS();
        $this->getView()->registerJs($editorJs, View::POS_END);

    }

    private function getToolsOptions(){
        $resultTools = [];
        foreach ($this->tools as $tool){
            $resultTools[] = $this->fullPlugins[$tool]['var'];
        }

        return $resultTools;
    }

    private function  getEditorJS(){
        $fieldValue = '';

        if ($this->hasModel()) {
            $fieldValue = $this->model[$this->attribute];
        } else {
            $fieldValue = $this->value;
        }

        $confing = [
            'holder' => $this->containerOptions['id'],
            'placeholder' => 'Начало документа',
            'tools' => "#TOOLS#",
            'data' => "#VALUE#"
        ];

        $json = Json::encode($confing);
        $json = str_replace(["\"#TOOLS#\"", "\"#VALUE#\""], ["{".implode(',', $this->getToolsOptions())."}", $fieldValue], $json);

        // TODO: Make normal saving

        $js = "editor".$this->containerOptions['id']." = new EditorJS(".$json.");
            
            
            $('form').submit(function(){
                form = this;
            
                console.log('submit :' + $(form).attr('class'))
                if(!$(form).hasClass('saved')){
                    if( $(form).hasClass('saving') ){ return false;}
                    $(form).addClass('saving');
                
                
                    editor".$this->containerOptions['id'].".save().then((outputData) => {
                      $(form).removeClass('saving').addClass('saved');
                      saveddata = JSON.stringify(outputData)
                      $('.".$this->containerOptions['id']."').val(saveddata);
                      
                      console.log($('.".$this->containerOptions['id']."').val());
                      console.log('Article data: ', outputData);
//                    $(form).submit();
                    }).catch((error) => {
                      console.log('Saving failed: ', error)
                    });
                    return false;
                }
               
            });
        ";
        return $js;
    }


    private function addPluginsJs(){
        foreach ($this->tools as $item) {
            list($folder, $file) = $value;
            $path = $this->fullPlugins[$item]['file'];
            list(, $assetPath) = Yii::$app->assetManager->publish($path);

            $this->getView()->registerJsFile($assetPath,  ['position' => yii\web\View::POS_HEAD]);
        }
    }


    private function addExtraPlugins()
    {
        if (!is_array($this->extraPlugins) || !count($this->extraPlugins)) {
            return false;
        }

        foreach ($this->extraPlugins as $value) {
            list($folder, $file) = $value;
            $path = __DIR__.'/assets/editorjs/plugins/'.$folder.'/'.$file;
            list(, $assetPath) = Yii::$app->assetManager->publish($path);

//            $this->getView()->registerJsFile("https://cdn.jsdelivr.net/npm/@editorjs/header@latest",  ['position' => yii\web\View::POS_HEAD]);
            $this->getView()->registerJsFile($assetPath,  ['position' => yii\web\View::POS_HEAD]);
//

        }

    }
}
