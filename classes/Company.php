<?php
class Company {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getSettings() {
        $settings = $this->db->fetch("SELECT * FROM company_settings LIMIT 1");
        if(!$settings) {
            $this->db->query("INSERT INTO company_settings (company_name) VALUES ('Your Company Name')");
            $settings = $this->db->fetch("SELECT * FROM company_settings LIMIT 1");
        }
        return $settings;
    }
    
    public function update($data) {
        $settings = $this->getSettings();
        $blockTypo = isset($data['block_typography']) ? $data['block_typography'] : ($settings['block_typography'] ?? '');
        $favicon = isset($data['favicon']) ? $data['favicon'] : ($settings['favicon'] ?? null);
        return $this->db->query(
            "UPDATE company_settings SET company_name=?, address=?, phone=?, email=?, website=?, currency=?, default_validity_days=?, default_terms=?, logo=?, favicon=?, smtp_host=?, smtp_port=?, smtp_username=?, smtp_password=?, smtp_encryption=?, from_name=?, from_email=?, email_template=?, table_font_family=?, table_font_size=?, table_font_style=?, table_text_color=?, table_line_height=?, table_font_weight=?, table_letter_spacing=?, table_text_transform=?, table_text_decoration=?, default_authorized_signature=?, block_typography=?, updated_at=NOW() WHERE id=?",
            [
                $data['company_name'], $data['address'], $data['phone'], $data['email'], $data['website'],
                $data['currency'], $data['default_validity_days'], $data['default_terms'], $data['logo'],
                $favicon,
                $data['smtp_host'], $data['smtp_port'], $data['smtp_username'], $data['smtp_password'],
                $data['smtp_encryption'], $data['from_name'], $data['from_email'], $data['email_template'],
                $data['table_font_family'], $data['table_font_size'], $data['table_font_style'], $data['table_text_color'],
                $data['table_line_height'], $data['table_font_weight'], $data['table_letter_spacing'], $data['table_text_transform'], $data['table_text_decoration'],
                $data['default_authorized_signature'],
                $blockTypo,
                $settings['id']
            ]
        );
    }
    
    public function updateLogo($logo) {
        $settings = $this->getSettings();
        return $this->db->query("UPDATE company_settings SET logo=? WHERE id=?", [$logo, $settings['id']]);
    }

    public function getBlockTypography($block = null) {
        $settings = $this->getSettings();
        $json = $settings['block_typography'] ?? '';
        $data = $json ? json_decode($json, true) : [];
        if(!is_array($data)) $data = [];
        if($block) {
            return $data[$block] ?? [];
        }
        return $data;
    }

    public function getBlockStyle($block) {
        $t = $this->getBlockTypography($block);
        $props = [];
        $map = [
            'font_family' => 'font-family',
            'font_size' => 'font-size',
            'font_style' => 'font-style',
            'font_weight' => 'font-weight',
            'line_height' => 'line-height',
            'letter_spacing' => 'letter-spacing',
            'text_transform' => 'text-transform',
            'text_decoration' => 'text-decoration',
            'text_color' => 'color',
        ];
        foreach($map as $key => $css) {
            if(!empty($t[$key])) {
                $props[] = $css . ': ' . $t[$key];
            }
        }
        return implode('; ', $props);
    }
}
?>
