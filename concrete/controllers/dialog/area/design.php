<?php
namespace Concrete\Controller\Dialog\Area;

use Concrete\Core\Area\CustomStyle;
use Concrete\Core\Page\EditResponse;
use Concrete\Core\StyleCustomizer\Inline\StyleSet;
use Concrete\Controller\Backend\UserInterface\Page as BackendPageController;

class Design extends BackendPageController
{
    /**
     * @deprecated What's deprecated is the "public" part.
     *
     * @var \Concrete\Core\Area\Area
     */
    public $area;

    protected $viewPath = '/dialogs/area/design';

    public function on_start()
    {
        parent::on_start();
        $this->area = \Area::getOrCreate($this->page, $_REQUEST['arHandle']);
        $this->permissions = new \Permissions($this->area);
        $this->set('a', $this->area);
    }
    protected function canAccess()
    {
        return $this->permissions->canEditAreaDesign();
    }

    public function reset()
    {
        $a = $this->area;
        $nvc = $this->page->getVersionToModify();
        $styleObject = $nvc->getAreaCustomStyle($a);
        $pr = new EditResponse();
        if (is_object($styleObject)) {
            // Set the old style id, so we can remove any hardcoded styles
            $pr->setAdditionalDataAttribute('oldIssID',$styleObject->getCustomStyleID());
        }
        $nvc->resetAreaCustomStyle($a);

        $pr->setPage($this->page);
        $pr->setAdditionalDataAttribute('aID', $this->area->getAreaID());
        $pr->setAdditionalDataAttribute('arHandle', $this->area->getAreaHandle());
        $pr->setMessage(t('Custom design reset.'));
        $pr->outputJSON();
    }

    public function submit()
    {
        if ($this->validateAction() && $this->canAccess()) {
            $a = $this->area;
            $oldStyle = $this->page->getAreaCustomStyle($a);
            $oldStyleSet = null;
            if (is_object($oldStyle)) {
                $oldStyleSet = $oldStyle->getStyleSet();
            }

            $nvc = $this->page->getVersionToModify();

            $set = StyleSet::populateFromRequest($this->request);
            if (is_object($set)) {
                $set->save();
                $nvc->setCustomStyleSet($a, $set);
            } elseif ($oldStyleSet) {
                $nvc->resetAreaCustomStyle($this->area);
            }

            $pr = new EditResponse();
            $pr->setPage($this->page);
            $pr->setAdditionalDataAttribute('aID', $this->area->getAreaID());
            $pr->setAdditionalDataAttribute('arHandle', $this->area->getAreaHandle());

            if (is_object($oldStyleSet)) {
                $pr->setAdditionalDataAttribute('oldIssID', $oldStyleSet->getID());
            }

            if (is_object($set)) {
                $pr->setAdditionalDataAttribute('issID', $set->getID());
                $style = new CustomStyle($set, $this->area, $this->page->getCollectionThemeObject());
                $css = $style->getCSS();
                if ($css !== '') {
                    $pr->setAdditionalDataAttribute('css', $style->getStyleWrapper($style->getCSS()));
                }
                $pr->setAdditionalDataAttribute('containerClass', $style->getContainerClass());
            }

            $pr->setMessage(t('Design updated.'));
            $pr->outputJSON();
        }
    }

    public function action()
    {
        $url = call_user_func_array('parent::action', func_get_args());
        $url .= '&arHandle=' . h($this->area->getAreaHandle());

        return $url;
    }

    public function view()
    {
    }
}
