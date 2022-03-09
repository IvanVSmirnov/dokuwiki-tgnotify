<?php
/**
 * DokuWiki Plugin Telegram Notifier (Action Component)
 *
 * @license Apache 2.0 http://www.apache.org/licenses/
 *
 */

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');


class action_plugin_tgnotify extends \dokuwiki\Extension\ActionPlugin
{
    const __PLUGIN_VERSION__ = '1.0.4';

    /**
     * plugin should use this method to register its handlers with the DokuWiki's event controller
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object. Also available as global $EVENT_HANDLER
     *
     * @return not required
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('COMMON_WIKIPAGE_SAVE', 'AFTER', $this, '_handle');
    }


    /**
     * custom event handler
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  the parameters passed to register_hook when this
     *                           handler was registered
     *
     * @return   not required
     */
    public function _handle(Doku_Event $event, $param)
    {
        if ( $this->getConf('enable') ) {
        $this->transmitMessage($this->prepareMessage($event), $this->getConf('silent'));
        }
    }

    
    /**
     * Prepare message text
     *
     * @param Doku_Event $event  event object by reference
     *
     * @return   string
     */
    private function prepareMessage(Doku_Event $event)
    {
        $message = '';

        // Page change type
        switch ($event->data['changeType']) {
            case 'C':
                $message .= $this->getLang('page-added') . PHP_EOL;
                break;
            case 'D':
                $message .= $this->getLang('page-deleted') . PHP_EOL;
                break;
            case 'E':
                $message .= $this->getLang('page-modified') . PHP_EOL;
                break;
            case 'e':
                $message .= $this->getLang('page-modified-minor') . PHP_EOL;
                break;
            case 'R':
                $message .= $this->getLang('page-reverted') . PHP_EOL;
                break;
        }

        // Add row with page name and url
        $pagename = $event->data['id'];
        $pageurl = rtrim(DOKU_URL, '/') . '/' . $pagename;
        $message .= sprintf($this->getLang('pagename'), $pagename, $pageurl) . PHP_EOL;

        // Add row with page size diff (in bytes)
        $message .= sprintf($this->getLang('sizechange'), $event->data['sizechange']) . PHP_EOL;

        // Add row with username (if logged in) or IP address
        global $USERINFO;
        if ( $this->getConf('showuser') && isset($USERINFO['name']) ) {
            $username = $USERINFO['name'];
            $message .= sprintf($this->getLang('username'), $username) . PHP_EOL;
        }

        // Add row with  IP address
        if ( $this->getConf('showaddr') ) {
            $useraddr = $_SERVER[REMOTE_ADDR];
            $message .= sprintf($this->getLang('useraddr'), $useraddr) . PHP_EOL;
        }

        return $message;
    }

    /**
     * Send telegram message
     *
     * @param string $text  message text
     *
     * @return   not required
     */
    private function transmitMessage($text, $silent)
    {
        $token = $this->getConf('token');
        $chatid = $this->getConf('chatid');

        $ch = curl_init();
        curl_setopt_array(
            $ch,
            array(
                CURLOPT_URL => 'https://api.telegram.org/bot' . $token . '/sendMessage',
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_POSTFIELDS => array(
                    'chat_id' => $chatid,
                    'text' => $text,
                    'parse_mode' => 'MarkdownV2',
                    'silent' => $silent,
                ),
            )
        );
        curl_exec($ch);
    }

}
