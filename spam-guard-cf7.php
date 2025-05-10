<?php
/*
Plugin Name: SPAM guard for Contact Form 7
Plugin URI: 
Description: 海外からの大量迷惑問い合わせを排除するプラグインです。お問い合わせ内容に日本語が含まれていない場合は送信されません
Version: 1.0.0
Author: 
License: GPL v2 or later
Text Domain: spam-guard-cf7
*/

if (!defined('ABSPATH')) {
    exit;
}

class SpamGuardCF7 {
    private static $instance = null;
    private $options;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_submenu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('wpcf7_validate_textarea', array($this, 'validate_japanese_text'), 10, 2);
        add_filter('wpcf7_validate_textarea*', array($this, 'validate_japanese_text'), 10, 2);
        add_filter('wpcf7_validate_text', array($this, 'validate_japanese_text'), 10, 2);
        add_filter('wpcf7_validate_text*', array($this, 'validate_japanese_text'), 10, 2);
        
        $this->options = get_option('spam_guard_cf7_options', array(
            'message_field' => 'your-message',
            'check_sender' => 'no',
            'sender_field' => 'your-name'
        ));
    }
    
    public function add_plugin_submenu() {
        add_submenu_page(
            'wpcf7',
            'お問合せ制限',
            'お問合せ制限',
            'manage_options',
            'spam-guard-cf7',
            array($this, 'create_admin_page')
        );
    }
    
    public function register_settings() {
        register_setting('spam_guard_cf7_settings', 'spam_guard_cf7_options');
        
        add_settings_section(
            'spam_guard_cf7_section',
            '制限設定',
            null,
            'spam-guard-cf7'
        );
        
        add_settings_field(
            'message_field',
            'お問合せ本文のフィールドID',
            array($this, 'message_field_callback'),
            'spam-guard-cf7',
            'spam_guard_cf7_section'
        );
        
        add_settings_field(
            'check_sender',
            '差出人名もチェックする',
            array($this, 'check_sender_callback'),
            'spam-guard-cf7',
            'spam_guard_cf7_section'
        );
        
        add_settings_field(
            'sender_field',
            '差出人名フィールドID',
            array($this, 'sender_field_callback'),
            'spam-guard-cf7',
            'spam_guard_cf7_section'
        );
    }
    
    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>お問合せ制限設定</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('spam_guard_cf7_settings');
                do_settings_sections('spam-guard-cf7');
                submit_button();
                ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            function toggleSenderField() {
                var checkSender = $('input[name="spam_guard_cf7_options[check_sender]"]:checked').val();
                var senderFieldRow = $('#sender_field').closest('tr');
                if (checkSender === 'yes') {
                    senderFieldRow.show();
                } else {
                    senderFieldRow.hide();
                }
            }
            
            $('input[name="spam_guard_cf7_options[check_sender]"]').change(function() {
                toggleSenderField();
            });
            
            toggleSenderField();
        });
        </script>
        <?php
    }
    
    public function message_field_callback() {
        $value = isset($this->options['message_field']) ? $this->options['message_field'] : 'your-message';
        echo '<input type="text" name="spam_guard_cf7_options[message_field]" value="' . esc_attr($value) . '" class="regular-text">';
    }
    
    public function check_sender_callback() {
        $value = isset($this->options['check_sender']) ? $this->options['check_sender'] : 'no';
        echo '<label><input type="radio" name="spam_guard_cf7_options[check_sender]" value="yes"' . checked($value, 'yes', false) . '> はい</label>&nbsp;&nbsp;';
        echo '<label><input type="radio" name="spam_guard_cf7_options[check_sender]" value="no"' . checked($value, 'no', false) . '> いいえ</label>';
    }
    
    public function sender_field_callback() {
        $value = isset($this->options['sender_field']) ? $this->options['sender_field'] : 'your-name';
        echo '<input type="text" id="sender_field" name="spam_guard_cf7_options[sender_field]" value="' . esc_attr($value) . '" class="regular-text">';
    }
    
    public function validate_japanese_text($result, $tag) {
        if ($tag->name == $this->options['message_field']) {
            $text = isset($_POST[$tag->name]) ? $_POST[$tag->name] : '';
            if (!empty($text) && !preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FFF}]/u', $text)) {
                $result->invalidate($tag, '英語でのお問い合わせには対応しておりません');
            }
        }
        
        if ($this->options['check_sender'] === 'yes' && $tag->name == $this->options['sender_field']) {
            $text = isset($_POST[$tag->name]) ? $_POST[$tag->name] : '';
            if (!empty($text) && !preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FFF}]/u', $text)) {
                $result->invalidate($tag, '英語でのお問い合わせには対応しておりません');
            }
        }
        
        return $result;
    }
}

// プラグインの初期化
add_action('plugins_loaded', array('SpamGuardCF7', 'get_instance'));
