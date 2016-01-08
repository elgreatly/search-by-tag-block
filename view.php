<?php defined('C5_EXECUTE') or die('Access Denied.');
if (isset($error)) {
    ?><?php echo $error?><br/><br/><?php
}

if (!isset($query) || !is_string($query)) {
    $query = array();
}else{
	$query = explode(',', $query);
}
?>
<form action="<?php echo $view->url($resultTargetURL)?>" method="get" class="ccm-search-block-form searchAjaxForm">
	<div id="searchResults" class="searchPage">
	<div class="forminputTags">
	<?php
    if (isset($title) && ($title !== '')) {
        ?><label class="labelSearch"><?php echo h($title)?></label><?php
    }
	if ($query === '') {
        ?><input name="search_paths[]" type="hidden" value="<?php echo htmlentities($baseSearchPath, ENT_COMPAT, APP_CHARSET) ?>" /><?php
    } elseif (isset($_REQUEST['search_paths']) && is_array($_REQUEST['search_paths'])) {
        foreach ($_REQUEST['search_paths'] as $search_path) {
            ?><input name="search_paths[]" type="hidden" value="<?php echo htmlentities($search_path, ENT_COMPAT, APP_CHARSET) ?>" /><?php
        }
    }
    ?>
    <input name="query" type="hidden" id="searchValue" />
    <input type="hidden" id="searchURLAjax" value="<?php echo $view->action('search_by_tag'); ?>" />
    <?php 
    $c = Page::getCurrentPage();
	if (!$c->isEditMode()) { ?>
    <select id="searchSelectValue" multiple>
    	<?php 
    	$i = 0;
		$ak = CollectionAttributeKey::getByHandle('tags');
		$akc = $ak->getController();
		$ttags = $akc->getOptions();
		foreach($ttags as $t) {
			?>
			<option value="<?php echo $t->value; ?>" <?php echo (in_array($t->value, $query))? 'selected' : '' ?> ><?php echo $t->value; ?></option>
		<?php 
			$i++;
		} ?>
    </select>
    <?php }else{ ?>
    	<input type="text" class="ccm-search-block-text" />
    <?php } ?>
    <button name="submit" type="submit" class="btn btn-default ccm-search-block-submit icon-search"><?php echo h($buttonText)?></button>
   	</div>
   	<div class="formcontentTags">
   		<div class="searchTagLoad"></div>
      <?php
    if (isset($do_search) && $do_search) { ?>
    	<?php
        if (count($results) == 0) { ?>
            	<h4 style="margin-bottom:30px"><?php echo t('There were no results found. Please try another keyword or phrase.')?></h4>
            <?php
        } else {
            $tt = Core::make('helper/text');
            ?>
            	<div class="searchTitle">
					<h4>erweiterte suche</h4>
				</div>
				<div class="searchSeparator"></div>
				<div class="searchList">
            	<?php
            	$ih = Loader::helper('image');
                foreach ($results as $r) {
				   	$oPage = Page::getById($r->getCollectionID());
               		$oThumb = $oPage->getAttribute('thumbnail');
                    ?>
                    <div class="searchSingle">
                    	<?php
	                    if(isset($oThumb) && $oThumb != false){ ?>
                    	<div class="SearchImage">
                    		<a href="<?php echo $r->getCollectionLink()?>">
                    			<?php
									$image = $ih->getThumbnail($oThumb, 270, 200, true);
								?>
									<img src="<?php echo $image->src ?>" alt="<?php echo $r->getCollectionName(); ?>" />
							</a>
						</div>
						<?php
						}
						?>
                    	<div class="singleSearchContent">
	                        <a href="<?php echo $r->getCollectionLink()?>"><?php
	                            if ($r->getCollectionDescription()) {
	                                echo $tt->shortText($r->getCollectionDescription(), 100);
	                                ?><br/><?php
	
	                            }
	                            echo $currentPageBody;
	                            ?>
	                        </a>
                        </div>
                    </div>
                    <div class="searchSeparator"></div>
                    <?php
                }
            ?>
            </div>
            <?php
            $pages = $pagination->getCurrentPageResults();
            if ($pagination->getTotalPages() > 1 && $pagination->haveToPaginate()) {
                $showPagination = true;
                echo $pagination->renderDefaultView();
            }
        }
    }
    
?>
</div>
</div>
</form>
<?php
