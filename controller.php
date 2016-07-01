<?php
namespace Application\Block\SearchByTags;

use Loader;
use CollectionAttributeKey;
use \Concrete\Core\Page\PageList;
use \Concrete\Core\Block\BlockController;
use Page;
use Core;

class Controller extends BlockController
{
    protected $btTable = 'btSearchByTags';
    protected $btInterfaceWidth = "400";
    protected $btInterfaceHeight = "420";
    protected $btWrapperClass = 'ccm-ui';
    protected $btExportPageColumns = array('postTo_cID');

    public $title = "";
    public $buttonText = ">";
    public $baseSearchPath = "";
    public $resultsURL = "";
    public $postTo_cID = "";
	public $relation = "";

    protected $hColor = '#EFE795';

    public function highlightedMarkup($fulltext, $highlight)
    {
        if (!$highlight) {
            return $fulltext;
        }

        $this->hText = $fulltext;
        $this->hHighlight  = $highlight;
        $this->hText = @preg_replace('#' . preg_quote($this->hHighlight, '#') . '#ui', '<span class="searchedWord">$0</span>', $this->hText );

        return $this->hText;
    }

    public function highlightedExtendedMarkup($fulltext, $highlight)
    {
        $text = @preg_replace("#\n|\r#", ' ', $fulltext);

        $matches = array();
        $highlight = str_replace(array('"',"'","&quot;"),'',$highlight); // strip the quotes as they mess the regex

        if (!$highlight) {
            $text = Loader::helper('text')->shorten($fulltext, 180);
            if (strlen($fulltext) > 180) {
                $text .= '&hellip;<wbr>';
            }

            return $text;
        }

        $regex = '([[:alnum:]|\'|\.|_|\s]{0,45})'. preg_quote($highlight, '#') .'([[:alnum:]|\.|_|\s]{0,45})';
        preg_match_all("#$regex#ui", $text, $matches);

        if (!empty($matches[0])) {
            $body_length = 0;
            $body_string = array();
            foreach ($matches[0] as $line) {
                $body_length += strlen($line);

                $r = $this->highlightedMarkup($line, $highlight);
                if ($r) {
                    $body_string[] = $r;
                }
                if($body_length > 150)
                    break;
            }
            if(!empty($body_string))

                return @implode("&hellip;<wbr>", $body_string);
        }
    }

    public function setHighlightColor($color)
    {
        $this->hColor = $color;
    }

    /**
	 * Used for localization. If we want to localize the name/description we have to include this
	 */
    public function getBlockTypeDescription()
    {
        return t("Add a search box to your site.");
    }

    public function getBlockTypeName()
    {
        return t("Search By Tags");
    }

    public function __construct($obj = null)
    {
        parent::__construct($obj);
    }

    public function indexExists()
    {
        $db = Loader::db();
        $numRows = $db->GetOne('select count(cID) from PageSearchIndex');

        return ($numRows > 0);
    }

    public function view()
    {
    	$this->requireAsset('select2');
        $c = Page::getCurrentPage();
		$this->set('current_page',$c);
        $this->set('title', $this->title);
        $this->set('buttonText', $this->buttonText);
        $this->set('baseSearchPath', $this->baseSearchPath);
        $this->set('postTo_cID', $this->postTo_cID);
        $resultsURL = $c->getCollectionPath();

        if ($this->resultsURL != '') {
            $resultsURL = $this->resultsURL;
        } elseif ($this->postTo_cID != '') {
            $resultsPage = Page::getById($this->postTo_cID);
            $resultsURL = $resultsPage->cPath;
        }
        $resultsURL = Loader::helper('text')->encodePath($resultsURL);

        $this->set('resultTargetURL', $resultsURL);
		//echo $_REQUEST['query'];
        //run query if display results elsewhere not set, or the cID of this page is set
        if ($this->postTo_cID == '') {
            if ( !empty($_REQUEST['query']) || isset($_REQUEST['akID']) || isset($_REQUEST['month'])) {
                $this->do_search();
            }
        }
    }

    public function save($data)
    {
        $args['title'] = isset($data['title']) ? $data['title'] : '';
        $args['buttonText'] = isset($data['buttonText']) ? $data['buttonText'] : '';
        $args['baseSearchPath'] = isset($data['baseSearchPath']) ? $data['baseSearchPath'] : '';
        if ( $args['baseSearchPath']=='OTHER' && intval($data['searchUnderCID'])>0 ) {
            $customPathC = Page::getByID( intval($data['searchUnderCID']) );
            if( !$customPathC )    $args['baseSearchPath']='';
            else $args['baseSearchPath'] = $customPathC->getCollectionPath();
        }
        if( trim($args['baseSearchPath'])=='/' || strlen(trim($args['baseSearchPath']))==0 )
            $args['baseSearchPath']='';

        if ( intval($data['postTo_cID'])>0 ) {
            $args['postTo_cID'] = intval($data['postTo_cID']);
        } else {
            $args['postTo_cID'] = '';
        }

        $args['resultsURL'] = ( $data['externalTarget']==1 && strlen($data['resultsURL'])>0 ) ? trim($data['resultsURL']) : '';
		$args['relation'] = $data['relation'];
		$this->relation = $args['relation'];
        parent::save($args);
		
    }

    public $reservedParams=array('page=','query[]=','search_paths[]=','submit=','search_paths%5B%5D=' );
    
    public function do_search()
    {
        $q = $_REQUEST['query'];
        // i have NO idea why we added this in rev 2000. I think I was being stupid. - andrew
        // $_q = trim(preg_replace('/[^A-Za-z0-9\s\']/i', ' ', $_REQUEST['query']));
        $_q = $q;
        $ipl = new PageList();
        $aksearch = false;
        if (is_array($_REQUEST['akID'])) {
            foreach ($_REQUEST['akID'] as $akID => $req) {
                $fak = CollectionAttributeKey::getByID($akID);
                if (is_object($fak)) {
                    $type = $fak->getAttributeType();
                    $cnt = $type->getController();
                    $cnt->setAttributeKey($fak);
                    $cnt->searchForm($ipl);
                    $aksearch = true;
                }
            }
        }
		
        if (isset($_REQUEST['month']) && isset($_REQUEST['year'])) {
            $year = @intval($_REQUEST['year']);
            $month = abs(@intval($_REQUEST['month']));
            if (strlen(abs($year)) < 4) {
                $year = (($year < 0) ? '-' : '') . str_pad($year, 4, '0', STR_PAD_LEFT);
            }
            if ($month < 12) {
                $month = str_pad($month, 2, '0', STR_PAD_LEFT);
            }
            $daysInMonth = date('t', strtotime("$year-$month-01"));
            $dh = Core::make('helper/date');
            /* @var $dh \Concrete\Core\Localization\Service\Date */
            $start = $dh->toDB("$year-$month-01 00:00:00", 'user');
            $end = $dh->toDB("$year-$month-$daysInMonth 23:59:59", 'user');
            $ipl->filterByPublicDate($start, '>=');
            $ipl->filterByPublicDate($end, '<=');
            $aksearch = true;
        }

        if (empty($_REQUEST['query']) && $aksearch == false) {
            return false;
        }
        if (isset($_REQUEST['query'])) {
            $ak = CollectionAttributeKey::getByHandle('tags');
			$akc = $ak->getController();
			$isMultiSelect = $akc->getAllowMultipleValues();
			$db = Loader::db();
			$criteria = array();
			$searchQuery = explode(',', $_REQUEST['query']);
			if(is_array($searchQuery)){
				foreach ($searchQuery as $v) {
					$escapedValue = $v;
					if ($isMultiSelect) {
						$criteria[] = "(ak_tags LIKE '%\n{$escapedValue}\n%')";
					} else {
						$criteria[] = "(ak_tags = '\n{$escapedValue}\n')";
					}
				}
				$where = '(' . implode($this->relation, $criteria) . ')';
				$ipl->filter(false, $where);
			}
        }
		
		if ( is_array($_REQUEST['search_paths']) ) {
            foreach ($_REQUEST['search_paths'] as $path) {
                if(!strlen($path)) continue;
                $ipl->filterByPath($path);
            }
        } elseif ($this->baseSearchPath != '') {
            $ipl->filterByPath($this->baseSearchPath);
        }
		
        // TODO fix this
        //$ipl->filter(false, '(ak_exclude_search_index = 0 or ak_exclude_search_index is null)');

        $pagination = $ipl->getPagination();
        $results = $pagination->getCurrentPageResults();
        $this->set('query', $q);
        $this->set('results', $results);
        $this->set('do_search', true);
        $this->set('searchList', $ipl);
        $this->set('pagination', $pagination);
    }
    
    function action_search_by_tag(){
		$q = $_GET['query'];
        // i have NO idea why we added this in rev 2000. I think I was being stupid. - andrew
        // $_q = trim(preg_replace('/[^A-Za-z0-9\s\']/i', ' ', $_REQUEST['query']));
        $_q = $q;
        $ipl = new PageList();
        $aksearch = false;
        if (empty($_GET['query']) && $aksearch == false) {
            return false;
        }
        if (isset($_GET['query'])) {
            $ak = CollectionAttributeKey::getByHandle('tags');
			$akc = $ak->getController();
			$isMultiSelect = $akc->getAllowMultipleValues();
			$db = Loader::db();
			$criteria = array();
			$searchQuery = explode(',', $_GET['query']);
            if ( is_array($_REQUEST['search_paths']) ) {
                foreach ($_REQUEST['search_paths'] as $path) {
                    if(!strlen($path)) continue;
                    $ipl->filterByPath($path);
                }
            } elseif ($this->baseSearchPath != '') {
                $ipl->filterByPath($this->baseSearchPath);
            }
			if(is_array($searchQuery)){
				foreach ($searchQuery as $v) {
					$escapedValue = $v;
					if ($isMultiSelect) {
						$criteria[] = "(ak_tags LIKE '%\n{$escapedValue}\n%')";
					} else {
						$criteria[] = "(ak_tags = '\n{$escapedValue}\n')";
					}
				}
				$where = '(' . implode($this->relation, $criteria) . ')';
				$ipl->filter(false, $where);
			}
        }
        $pagination = $ipl->getPagination();
        $results = $pagination->getCurrentPageResults();
		$resultImage = array();
		$linkProduct = array();
		$ih = Loader::helper('image');
        $counter = 0;
        $pageName = [];
        $pageDescription = [];
        $counter = 0;
		foreach ($results as $r) {
			$linkProduct[] = $r->getCollectionLink();
		   	$oPage = Page::getById($r->getCollectionID());
       		$oThumb = $oPage->getAttribute('thumbnail');
			if(isset($oThumb) && $oThumb != false){
				$resultImage[] = $ih->getThumbnail($oThumb, 70, 95, false)->src;
			}else{
                $resultImage[] = '';
            }
            $pageName[] = $r->getCollectionName();
            $pageDescription[] = $r->getCollectionDescription();
            $counter++;
		}
		$paginationView;
        if ($pagination->getTotalPages() > 1 && $pagination->haveToPaginate()) {
            $showPagination = true;
            $paginationView = $pagination->renderDefaultView();
        }
		$totalPageNumber;
		if (count($results) != 0) {
			$totalPageNumber = $pagination->getTotal();
		}else{
			$totalPageNumber = 0;
		} 
        //print_r($results);
		$ajaxSearchResult = array('result' => $results, 'pageNames' => $pageName, 'pageDescription' => $pageDescription, 'page_thumb' => $resultImage, 'pagination' => $paginationView, 'product_links' => $linkProduct, 'total_number' => $totalPageNumber);
		if(isset($_GET['query']) && !empty($_GET['query'])) {
			echo json_encode($ajaxSearchResult);
		}else{
			echo json_encode('');
		}
		die;
	}
}