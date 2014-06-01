<?php
namespace FluidTYPO3\FluidcontentCore\Provider;
/*****************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Claus Due <claus@namelesscoder.net>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 *****************************************************************/

use FluidTYPO3\Flux\Form;
use FluidTYPO3\Flux\Provider\AbstractProvider;
use FluidTYPO3\Flux\Provider\ProviderInterface;
use FluidTYPO3\Flux\Utility\ExtensionNamingUtility;
use FluidTYPO3\Flux\Utility\RecursiveArrayUtility;
use FluidTYPO3\Flux\Utility\PathUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * ConfigurationProvider for records in tt_content
 *
 * This Configuration Provider has the lowest possible priority
 * and is only used to execute a set of hook-style methods for
 * processing records. This processing ensures that relationships
 * between content elements get stored correctly -
 *
 * @package Flux
 * @subpackage Provider
 */
class ContentProvider extends AbstractProvider implements ProviderInterface {

	/**
	 * @var string
	 */
	protected $extensionKey = 'fluidcontent_core';

	/**
	 * @var integer
	 */
	protected $priority = 0;

	/**
	 * @var string
	 */
	protected $tableName = 'tt_content';

	/**
	 * @var string
	 */
	protected $fieldName = 'content_options';

	/**
	 * @var array
	 */
	protected static $variants = array();

	/**
	 * @var array
	 */
	protected static $versions = array();

	/**
	 * @return void
	 */
	public function initializeObject() {
		$typoScript = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
		$settings = (array) $typoScript['plugin.']['tx_fluidcontentcore.']['settings.'];
		$paths = (array) $typoScript['plugin.']['tx_fluidcontentcore.']['view.'];
		$this->templateVariables['settings'] = GeneralUtility::removeDotsFromTS($settings);
		$paths = GeneralUtility::removeDotsFromTS($paths);
		$paths = PathUtility::translatePath($paths);
		$this->templatePaths = $paths;
		$this->templatePathAndFilename = PathUtility::translatePath($settings['defaultTemplate']);
	}

	/**
	 * Note: This Provider will -always- trigger on any tt_content record
	 * but has the lowest possible (0) priority, ensuring that any
	 * Provider which wants to take over, can do so.
	 *
	 * @param array $row
	 * @param string $table
	 * @param string $field
	 * @param string $extensionKey
	 * @return boolean
	 */
	public function trigger(array $row, $table, $field, $extensionKey = NULL) {
		return ($table === $this->tableName && ($field === $this->fieldName || NULL === $field));
	}

	/**
	 * @param array $row
	 * @return Form
	 */
	public function getForm(array $row) {
		$form = parent::getForm($row);
		$variables = $this->templateVariables;
		$variables['record'] = $row;
		return $form;
	}

	/**
	 * @param string $contentType
	 * @return array
	 */
	public function getVariantExtensionKeysForContentType($contentType) {
		if (FALSE === isset($GLOBALS['TYPO3_CONF_VARS']['FluidTYPO3.FluidcontentCore']['variants'][$contentType])) {
			return array();
		}
		if (TRUE === isset(self::$variants[$contentType])) {
			return self::$variants[$contentType];
		}
		self::$variants[$contentType] = array();
		foreach ($GLOBALS['TYPO3_CONF_VARS']['FluidTYPO3.FluidcontentCore']['variants'][$contentType] as $variantExtensionKey) {
			$templatePathAndFilename = $this->getTemplatePathAndFilenameByExtensionKeyAndContentType($variantExtensionKey, $contentType);
			if (TRUE === file_exists(PathUtility::translatePath($templatePathAndFilename))) {
				array_push(self::$variants[$contentType], $variantExtensionKey);
			}
		}
		return self::$variants[$contentType];
	}

	/**
	 * @param string $contentType
	 * @param string $variant
	 * @return array
	 */
	public function getVariantVersions($contentType, $variant) {
		if (TRUE === isset(self::$versions[$contentType][$variant])) {
			return self::$versions[$contentType][$variant];
		}
		if (FALSE === isset(self::$versions[$contentType])) {
			self::$versions[$contentType] = array();
		}
		$paths = $this->configurationService->getViewConfigurationForExtensionName($variant);
		$versionsDirectory = rtrim($paths['templateRootPath'], '/') . '/CoreContent/' . ucfirst($contentType) . '/';
		$versionsDirectory = PathUtility::translatePath($versionsDirectory);
		if (FALSE === is_dir($versionsDirectory)) {
			self::$versions[$contentType][$variant] = array();
		} else {
			$files = glob($versionsDirectory . '*.html');
			foreach ($files as &$file) {
				$file = basename($file, '.html');
			}
			self::$versions[$contentType][$variant] = $files;
		}
		return self::$versions[$contentType][$variant];
	}

	/**
	 * @param string $extensionKey
	 * @param string $contentType
	 * @param string $contentVersion
	 * @return string
	 */
	protected function getTemplatePathAndFilenameByExtensionKeyAndContentType($extensionKey, $contentType, $contentVersion = '') {
		$paths = $this->configurationService->getViewConfigurationForExtensionName($extensionKey);
		$templatePathAndFilename = rtrim($paths['templateRootPath'], '/') . '/CoreContent/' . ucfirst($contentType);
		if (FALSE === empty($contentVersion)) {
			$templatePathAndFilename .= '/' . $contentVersion;
		}
		$templatePathAndFilename .= '.html';
		return $templatePathAndFilename;
	}

	/**
	 * @param array $row
	 * @return string|NULL
	 */
	public function getExtensionKey(array $row) {
		if (FALSE === empty($row['content_variant'])) {
			return $row['content_variant'];
		}
		return $this->extensionKey;
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getTemplatePathAndFilename(array $row) {
		$extensionKey = $this->getExtensionKey($row);
		$template = $this->getTemplatePathAndFilenameByExtensionKeyAndContentType($extensionKey, $row['CType'], $row['content_version']);
		if (TRUE === file_exists(PathUtility::translatePath($template))) {
			return GeneralUtility::getFileAbsFileName($template);
		}
		return GeneralUtility::getFileAbsFileName($this->templatePathAndFilename);
	}

	/**
	 * @param array $row
	 * @return string
	 */
	public function getControllerActionFromRecord(array $row) {
		return strtolower($row['CType']);
	}

}
