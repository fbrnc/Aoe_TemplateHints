<?php

/**
 * Template hints
 *
 * @author Fabrizio Branca
 */
class Aoe_TemplateHints_Model_Observer {

	/**
	 * @var bool
	 */
	protected $showHints;

	/**
	 * @var bool
	 */
	protected $init = true;

	/**
	 * @var int
	 */
	protected $hintId = 0;

	/**
	 * @var Aoe_TemplateHints_Model_Renderer_Abstract
	 */
	protected $renderer;


    /**
     * @var array
     * */
    protected $aStatistics = array(
        self::TYPE_CACHED => 0,
        self::TYPE_IMPLICITLYCACHED => 0,
        self::TYPE_NOTCACHED => 0,
    );


	/**
	 * Check if hints should be displayed
	 *
	 * @return bool
	 */
	public function showHints() {
		if (is_null($this->showHints)) {
			$this->showHints = false;
			if (Mage::helper('core')->isDevAllowed()) {
				if (Mage::getModel('core/cookie')->get('ath') || Mage::getSingleton('core/app')->getRequest()->get('ath')) {
					$this->showHints = true;
				}
			}
		}
		return $this->showHints;
	}



	/**
	 * Get renderer
	 *
	 * @return Aoe_TemplateHints_Model_Renderer_Abstract
	 */
	public function getRenderer() {
		if (is_null($this->renderer)) {
			$rendererClass = Mage::getStoreConfig('dev/aoe_templatehints/templateHintRenderer');
			if (empty($rendererClass)) {
				Mage::throwException('No renderer configured');
			}
			$this->renderer = Mage::getSingleton($rendererClass);
			if (!is_object($this->renderer) || !$this->renderer instanceof Aoe_TemplateHints_Model_Renderer_Abstract) {
				Mage::throwException('Render must be an instanceof Aoe_TemplateHints_Model_Renderer_Abstract');
			}
		}
		return $this->renderer;
	}



	/**
	 * Event core_block_abstract_to_html_after
	 *
	 * @param Varien_Event_Observer $params
	 * @return void
	 * @author Fabrizio Branca
	 * @since 2011-01-24
	 */
	public function core_block_abstract_to_html_after(Varien_Event_Observer $params) {

		if (!$this->showHints()) {
			return;
		}

		if (substr(trim($params->getTransport()->getHtml()), 0, 4) == 'http') {
			return;
		}

		$wrappedHtml = '';

		if ($this->init) {
			$wrappedHtml = $this->getRenderer()->init($wrappedHtml);
			$this->init = false;
		}

		$block = $params->getBlock(); /* @var $block Mage_Core_Block_Abstract */

		$transport = $params->getTransport();

		$this->hintId++;
        $this->aStatistics[$blockInfo['cache-status']]++;

		$wrappedHtml .= $this->getRenderer()->render($block, $transport->getHtml(), $this->hintId);

        if($blockInfo['name'] =='core_profiler'){
            $wrappedHtml .= '<pre>'.print_r($this->aStatistics, true).'</pre>';
        }

		$transport->setHtml($wrappedHtml);
	}


}
