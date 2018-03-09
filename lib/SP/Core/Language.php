<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Core;

use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\Context\SessionContext;
use SP\Http\Request;

defined('APP_ROOT') || die();

/**
 * Class Language para el manejo del lenguaje utilizado por la aplicación
 *
 * @package SP
 */
class Language
{
    /**
     * Lenguaje del usuario
     *
     * @var string
     */
    public static $userLang = '';
    /**
     * Lenguaje global de la Aplicación
     *
     * @var string
     */
    public static $globalLang = '';
    /**
     * Estado de la localización. false si no existe
     *
     * @var string|false
     */
    public static $localeStatus;
    /**
     * Si se ha establecido a las de la App
     *
     * @var bool
     */
    protected static $appSet = false;
    /**
     * @var array Available languages
     */
    private static $langs = [
        'es_ES' => 'Español',
        'ca_ES' => 'Catalá',
        'en_US' => 'English',
        'de_DE' => 'Deutsch',
        'hu_HU' => 'Magyar',
        'fr_FR' => 'Français',
        'po_PO' => 'Polski',
        'ru_RU' => 'русский',
        'nl_NL' => 'Nederlands',
        'pt_BR' => 'Português'
    ];
    /**
     * @var ConfigData
     */
    protected $configData;
    /**
     * @var  SessionContext
     */
    protected $session;

    /**
     * Language constructor.
     *
     * @param SessionContext $session
     * @param Config $config
     */
    public function __construct(SessionContext $session, Config $config)
    {
        $this->session = $session;
        $this->configData = $config->getConfigData();

        ksort(self::$langs);
    }

    /**
     * Devolver los lenguajes disponibles
     *
     * @return array
     */
    public static function getAvailableLanguages()
    {
        return self::$langs;
    }

    /**
     * Establecer el lenguaje a utilizar
     *
     * @param bool $force Forzar la detección del lenguaje para los inicios de sesión
     */
    public function setLanguage($force = false)
    {
        $lang = $this->session->getLocale();

        if (empty($lang) || $force === true) {
            self::$userLang = $this->getUserLang();
            self::$globalLang = $this->getGlobalLang();

            $lang = self::$userLang ?: self::$globalLang;

            $this->session->setLocale($lang);
        }

        $this->setLocales($lang);
    }

    /**
     * Devuelve el lenguaje del usuario
     *
     * @return bool
     */
    private function getUserLang()
    {
        $userData = $this->session->getUserData();

        return ($userData->getId() > 0) ? $userData->getPreferences()->getLang() : '';
    }

    /**
     * Establece el lenguaje de la aplicación.
     * Esta función establece el lenguaje según esté definido en la configuración o en el navegador.
     */
    private function getGlobalLang()
    {
        $browserLang = $this->getBrowserLang();
        $configLang = $this->configData->getSiteLang();

        // Establecer a en_US si no existe la traducción o no es español
        if (!$configLang
            && !$this->checkLangFile($browserLang)
            && strpos($browserLang, 'es_') === false
        ) {
            $lang = 'en_US';
        } else {
            $lang = $configLang ?: $browserLang;
        }

        return $lang;
    }

    /**
     * Devolver el lenguaje que acepta el navegador
     *
     * @return mixed
     */
    private function getBrowserLang()
    {
        $lang = Request::getRequestHeaders('HTTP_ACCEPT_LANGUAGE');

        return $lang ? str_replace('-', '_', substr($lang, 0, 5)) : '';
    }

    /**
     * Comprobar si el archivo de lenguaje existe
     *
     * @param string $lang El lenguaje a comprobar
     * @return bool
     */
    private function checkLangFile($lang)
    {
        return file_exists(LOCALES_PATH . DIRECTORY_SEPARATOR . $lang);
    }

    /**
     * Establecer las locales de gettext
     *
     * @param string $lang El lenguaje a utilizar
     */
    public function setLocales($lang)
    {
        $lang .= '.utf8';
        $fallback = 'en_US.utf8';

        self::$localeStatus = setlocale(LC_MESSAGES, [$lang, $fallback]);

        putenv('LANG=' . $lang);
        setlocale(LC_ALL, [$lang, $fallback]);
        bindtextdomain('messages', LOCALES_PATH);
        textdomain('messages');
        bind_textdomain_codeset('messages', 'UTF-8');
    }

    /**
     * Establecer el lenguaje global para las traducciones
     */
    public function setAppLocales()
    {
        if ($this->configData->getSiteLang() !== $this->session->getLocale()) {
            $this->setLocales($this->configData->getSiteLang());

            self::$appSet = true;
        }
    }

    /**
     * Restablecer el lenguaje global para las traducciones
     */
    public function unsetAppLocales()
    {
        if (self::$appSet === true) {
            $this->setLocales($this->session->getLocale());

            self::$appSet = false;
        }
    }
}