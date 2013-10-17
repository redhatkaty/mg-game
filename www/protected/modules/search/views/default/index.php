<?php
Yii::import('zii.widgets.CListView');
class ListView extends CListView
{
    public $sortCriteria = array();

    /**
     * Renders the sorter.
     */
    public function renderSorter()
    {
        $controller     =       $this->getController();
        $url    =       $controller->createUrl($controller->getRoute());
        if($this->dataProvider->getItemCount()<=0 || !$this->enableSorting)
        {
            return;
        }
        echo CHtml::openTag('div',array('class'=>$this->sorterCssClass))."\n";
        echo $this->sorterHeader===null ? Yii::t('zii','Sort by: ') : $this->sorterHeader;
        echo CHtml::dropDownList(null,null,array(''=>'Select')+$this->sortCriteria,array(
            'id'=>'sortBy',
            'onchange'=>"$.fn.yiiListView.update('yw0',{ url:'".$url."?".ucfirst($controller->id)."_sort='+$('#sortBy').val()})"));

        echo $this->sorterFooter;
        echo CHtml::closeTag('div');
    }
}
?>

<div class="search-form" style="display: block;">
    <?php $this->renderPartial('_search', array(
        'model' => $model,
        'institutions' => $institutions
    )); ?>
</div><!-- search-form -->



<?php echo CHtml::beginForm('', 'post', array('id' => 'media-form'));
$tagDialog = $this->widget('MGTagJuiDialog');

// Maximum number of tags to show in the 'Top Tags' column.
$max_toptags = 15;

function generateImage($data)
{
    $media = CHtml::image(MGHelper::getMediaThumb($data->institution->url, $data->mime_type, $data->name), $data->name);
    return $media;
}
function generateImageURL ($data)
{
    $url = MGHelper::getMediaThumb($data->institution->url, $data->mime_type, $data->name);
    return $url;
}

function totalItemsFound($provider)
{
    $iterator = new CDataProviderIterator($provider);
    $i = 0;
    foreach($iterator as $tmp) {
        $i++;
    }
    return $i;
}




echo '


    <div class="main_content box">';
$options = array ('10' => '10', '15' => '15', '20'=>'20', '25'=>'25' );

$this->widget('ListView', array(
    'dataProvider'=>$model->search(true),
    'itemView'=>'_viewSearch',   // refers to the partial view named '_viewSearch'
    /*    'sortableAttributes' => array(
            'name' => Yii::t('app', 'Name'),
        ),*/
    'sortCriteria'=>array(
        'name'=>'Relevance   ',
        'name.desc'=>'A-Z   ',
        'name.asc'=>'Z-A    '),
    'ajaxUpdate'=>false,
    'enablePagination'=>true,
   // 'template'=>"{summary}<div>{sorter}\n{pager}</div>{items}\n<div>{sorter}\n{pager}</div>", //pager on top
    'template'=>"<div id = \"levelOneHolder\">{summary}<div class = \"itemsPerPage\">Items per page: " . CHtml::dropDownList('selectItemsPerPage', 'itemsPerPage', $options) . "</div>" . "{sorter}{pager}</div>{items}", //pager on top
    'summaryText'=>" ",

));

echo '</div></div>';
echo CHtml::endForm();

echo "<div id=\"totalItemsFound\">";
$itemsFound =  totalItemsFound($model->search(true));
if($itemsFound != 0) echo  'Your search ' .   "<div id=\"putFor\"> </div>"  .' '. "<div id=\"searchedValue\"> </div>" . ' returned ' . $itemsFound . ' results.';
echo "</div>";
?>

<script id="template-image_description" type="text/x-jquery-tmpl">
    <div class="delete right">X</div>
    <div class="image_div">
        <img src="${imageFullSize}" />
    </div>
    <div class="group text_descr">
        <!--<div><strong>${imageFullSize}</strong></div> --> <!-- pkostov do we need that? It must be description NOT img path ?-->
        <br />


        <div><strong>${collection} </strong></div>
        <div><strong>${institution}</strong></a></div>

        <div>Other media that may interest you:</div>
        <div id="related_items" class="group">
            {{each related}}
            <div interest_id="${id}" class="item">
                <img src="${thumbnail}" />
            </div>
            {{/each}}
        </div>
        <div id="tags">
            {{each tags}}
            ${tag},
            {{/each}}
        </div>
    </div>
</script>

<script id="template-video_description" type="text/x-jquery-tmpl">
    <div style="display: flex-box; float: left; padding: 10px 20px;">
        <video class="video" controls preload poster="' . $url_poster . '">
            <source src="${url_mp4}"></source>
            <source src="${url_webm}"></source>
        </video>
    </div>
</script>
<script id="template-audio_description" type="text/x-jquery-tmpl">
    <div style="display: flex-box; float: left; padding: 10px 20px;">
        <audio class="audio" controls preload>
            <source src="${url_mp3}"></source>
            <source src="${url_ogg}"></source>
        </audio>
    </div>
</script>