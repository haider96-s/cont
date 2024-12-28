<?php
use Joomunited\WPFramework\v1_0_6\Utilities;
?>
<?php if (Utilities::getInput('caninsert', 'GET', 'bool')) : ?>
    <div class="wpfd-insert-buttons">
    <?php if (Utilities::getInput('woocommerce', 'GET', 'bool')) : ?>
        <?php $maybeVariationId = Utilities::getInput('variation', 'GET', 'int'); ?>
            <a id="insertfiletowoo" class="ju-button ju-v3-button ju-v3-material" style="display: none;" href="#"
                onclick="if (window.parent) insertFileToWoo(<?php echo esc_html($maybeVariationId); ?>);"><?php esc_html_e('Insert this file', 'wpfd'); ?></a>
    <?php else : ?>
        <?php if (Utilities::getInput('elementorbuilder', 'GET', 'bool')) : ?>
            <a id="insertcategory" class="ju-button ju-v3-button ju-v3-material" href="#"
               onclick="if (window.parent) insertElementorCategory();"><?php esc_html_e('Insert this category', 'wpfd'); ?></a>
            <a id="insertfile" class="ju-button ju-v3-button ju-v3-material" style="display: none;" href="#"
               onclick="if (window.parent) insertElementorFile();"><?php esc_html_e('Insert this file', 'wpfd'); ?></a>
        <?php endif; ?>

        <?php if (Utilities::getInput('wpbakerybuilder', 'GET', 'bool')) : ?>
            <a id="insertcategory" class="ju-button ju-v3-button ju-v3-material" href="#"
               onclick="if (window.parent) insertWPBakeryCategory();"><?php esc_html_e('Insert this category', 'wpfd'); ?></a>
            <a id="insertfile" class="ju-button ju-v3-button ju-v3-material" style="display: none;" href="#"
               onclick="if (window.parent) insertWPBakeryFile();"><?php esc_html_e('Insert this file', 'wpfd'); ?></a>
        <?php endif; ?>

        <?php if (Utilities::getInput('avadabuilder', 'GET', 'bool')) : ?>
            <a id="insertcategory" class="ju-button ju-v3-button ju-v3-material" href="#"
               onclick="if (window.parent) insertAvadaCategory();"><?php esc_html_e('Insert this category', 'wpfd'); ?></a>
            <a id="insertfile" class="ju-button ju-v3-button ju-v3-material" style="display: none;" href="#"
               onclick="if (window.parent) insertAvadaFile();"><?php esc_html_e('Insert this file', 'wpfd'); ?></a>
        <?php endif; ?>

        <?php if (!Utilities::getInput('elementorbuilder', 'GET', 'bool') && !Utilities::getInput('wpbakerybuilder', 'GET', 'bool') && !Utilities::getInput('avadabuilder', 'GET', 'bool')) : ?>
            <a id="insertcategory" class="ju-button ju-v3-button ju-v3-material" href="#"
               onclick="if (window.parent) insertCategory();"><?php esc_html_e('Insert this category', 'wpfd'); ?></a>
            <a id="insertfile" class="ju-button ju-v3-button ju-v3-material" style="display: none;" href="#"
               onclick="if (window.parent) insertFile();"><?php esc_html_e('Insert this file', 'wpfd'); ?></a>
        <?php endif; ?>
    <?php endif; ?>
    </div>
<?php endif; ?>