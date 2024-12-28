<div class="wpfd_color_selection">
    <div class="wpfd-color-swatchers">
        <div data-color="#b2b2b2" title="<?php esc_html_e('Default', 'wpfd'); ?>" class="color"><i style="color: #b2b2b2" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#ac725e" title="<?php esc_html_e('Chocolate ice cream', 'wpfd'); ?>" class="color"><i style="color: #ac725e" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#d06b64" title="<?php esc_html_e('Old brick red', 'wpfd'); ?>" class="color"><i style="color: #d06b64" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#f83a22" title="<?php esc_html_e('Cardinal', 'wpfd'); ?>" class="color"><i style="color: #f83a22" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#fa573c" title="<?php esc_html_e('Wild strawberries', 'wpfd'); ?>" class="color"><i style="color: #fa573c" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#ff7537" title="<?php esc_html_e('Mars orange', 'wpfd'); ?>" class="color"><i style="color: #ff7537" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#ffad46" title="<?php esc_html_e('Yellow cab', 'wpfd'); ?>" class="color"><i style="color: #ffad46" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#42d692" title="<?php esc_html_e('Spearmint', 'wpfd'); ?>" class="color"><i style="color: #42d692" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#16a765" title="<?php esc_html_e('Vern fern', 'wpfd'); ?>" class="color"><i style="color: #16a765" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#7bd148" title="<?php esc_html_e('Asparagus', 'wpfd'); ?>" class="color"><i style="color: #7bd148" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#b3dc6c" title="<?php esc_html_e('Slime green', 'wpfd'); ?>" class="color"><i style="color: #b3dc6c" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#fbe983" title="<?php esc_html_e('Desert sand', 'wpfd'); ?>" class="color"><i style="color: #fbe983" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#fad165" title="<?php esc_html_e('Macaroni', 'wpfd'); ?>" class="color"><i style="color: #fad165" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#92e1c0" title="<?php esc_html_e('Sea foam', 'wpfd'); ?>" class="color"><i style="color: #92e1c0" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#9fe1e7" title="<?php esc_html_e('Pool', 'wpfd'); ?>" class="color"><i style="color: #9fe1e7" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#9fc6e7" title="<?php esc_html_e('Denim', 'wpfd'); ?>" class="color"><i style="color: #9fc6e7" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#4986e7" title="<?php esc_html_e('Rainy sky', 'wpfd'); ?>" class="color"><i style="color: #4986e7" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#9a9cff" title="<?php esc_html_e('Blue velvet', 'wpfd'); ?>" class="color"><i style="color: #9a9cff" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#b99aff" title="<?php esc_html_e('Purple dino', 'wpfd'); ?>" class="color"><i style="color: #b99aff" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#8f8f8f" title="<?php esc_html_e('Mouse', 'wpfd'); ?>" class="color"><i style="color: #8f8f8f" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#cabdbf" title="<?php esc_html_e('Mountain grey', 'wpfd'); ?>" class="color"><i style="color: #cabdbf" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#cca6ac" title="<?php esc_html_e('Earthworm', 'wpfd'); ?>" class="color"><i style="color: #cca6ac" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#f691b2" title="<?php esc_html_e('Bubble gum', 'wpfd'); ?>" class="color"><i style="color: #f691b2" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#cd74e6" title="<?php esc_html_e('Purple rain', 'wpfd'); ?>" class="color"><i style="color: #cd74e6" class="material-icons wpfd-folder">folder</i></div>
        <div data-color="#a47ae2" title="<?php esc_html_e('Toy eggplant', 'wpfd'); ?>" class="color"><i style="color: #a47ae2" class="material-icons wpfd-folder">folder</i></div>
        <?php foreach ($this->custom_colors as $color) : ?>
            <div data-color="<?php echo esc_attr($color); ?>" title="<?php esc_html_e('Custom color', 'wpfd'); ?>" class="color custom"><i style="color: <?php echo esc_attr($color); ?>" class="material-icons wpfd-folder">folder_shared</i></div>
        <?php endforeach; ?>
    </div>
    <div class="wpfd-color-box-wrapper">
        <div class="color-selection-box">
            <input type="text" class="ju-input wp-color-field-inline minicolors minicolors-input"/>
        </div>
        <div class="color-value">
            <div data-color="#a47ae2" title="<?php esc_html_e('Custom color', 'wpfd'); ?>" class="color js-select-color"><i style="color: #a47ae2" class="material-icons wpfd-folder">folder_shared</i></div>
            <input type="text" class="ju-input js-selected-color" />
        </div>
        <div class="wpfd-actions">
            <button type="button" class="ju-button ju-v3-button js-setcolor">Select</button>
        </div>
    </div>
</div>