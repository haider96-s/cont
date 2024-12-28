<div class="wpfd-contextmenu wpfd-folder-menu" style="display: none;">
    <ul class="wpfd-dropdown-menu">
        <?php if (wpfd_can_create_category()) : ?>
        <li data-action="folder.new">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000">
                <path d="M0 0h24v24H0V0z" fill="none"/>
                <path d="M20 6h-8l-2-2H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm0 12H4V6h5.17l2 2H20v10zm-8-4h2v2h2v-2h2v-2h-2v-2h-2v2h-2z"/>
            </svg>
            <span><?php esc_html_e('New Category', 'wpfd'); ?></span>
        </li>
        <?php endif; ?>
        <li data-action="folder.rename">
            <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><g><rect fill="none" height="24" width="24"/></g><g><g><polygon points="15,16 11,20 21,20 21,16"/><path d="M12.06,7.19L3,16.25V20h3.75l9.06-9.06L12.06,7.19z M5.92,18H5v-0.92l7.06-7.06l0.92,0.92L5.92,18z"/><path d="M18.71,8.04c0.39-0.39,0.39-1.02,0-1.41l-2.34-2.34C16.17,4.09,15.92,4,15.66,4c-0.25,0-0.51,0.1-0.7,0.29l-1.83,1.83 l3.75,3.75L18.71,8.04z"/></g></g></svg>
            <span><?php esc_html_e('Rename', 'wpfd'); ?></span>
        </li>
        <li data-action="folder.duplicate">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="24px" height="24px">
                <g transform="translate(0,-952.36218)">
                    <path d="M 21.53125 14 C 17.97983 14 15 16.93994 15 20.5 L 15 24 L 11.53125 24 C 7.9798304 24 5 26.93226 5 30.5 L 5 79.5 C 5 83.0677 7.9799329 86 11.53125 86 L 78.46875 86 C 82.020065 86 85 83.0677 85 79.5 L 85 76 L 88.46875 76 C 92.020168 76 95 73.0601 95 69.5 L 95 29.5 C 95 25.93993 92.020168 23 88.46875 23 L 57.1875 23 L 49.21875 14.875 A 3.0003 3.0003 0 0 0 47.0625 14 L 21.53125 14 z M 21.53125 20 L 45.8125 20 L 53.75 28.09375 A 3.0003 3.0003 0 0 0 55.875 29 L 88.46875 29 C 88.821692 29 89 29.18207 89 29.5 L 89 69.5 C 89 69.8181 88.821692 70 88.46875 70 L 21.53125 70 C 21.17829 70 21 69.8181 21 69.5 L 21 20.5 C 21 20.18206 21.17829 20 21.53125 20 z M 11.53125 30 L 15 30 L 15 69.5 C 15 73.0601 17.97983 76 21.53125 76 L 79 76 L 79 79.5 C 79 79.8104 78.821795 80 78.46875 80 L 11.53125 80 C 11.178187 80 11 79.8104 11 79.5 L 11 30.5 C 11 30.18974 11.17829 30 11.53125 30 z " transform="translate(0,952.36218)"/>
                </g>
            </svg>
            <span><?php esc_html_e('Duplicate Category', 'wpfd'); ?></span>
        </li>
        <li data-action="file.paste" class="menu-item-disabled">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000">
                <path d="M0 0h24v24H0V0z" fill="none"/>
                <path d="M19 2h-4.18C14.4.84 13.3 0 12 0S9.6.84 9.18 2H5c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm7 18H5V4h2v3h10V4h2v16z"/>
            </svg>
            <span><?php esc_html_e('Paste', 'wpfd'); ?><span class="clipboard-count"></span></span>
        </li>
        <li data-action="folder.refresh">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000">
                <path d="M0 0h24v24H0V0z" fill="none"/>
                <path d="M12 6v3l4-4-4-4v3c-4.42 0-8 3.58-8 8 0 1.57.46 3.03 1.24 4.26L6.7 14.8c-.45-.83-.7-1.79-.7-2.8 0-3.31 2.69-6 6-6zm6.76 1.74L17.3 9.2c.44.84.7 1.79.7 2.8 0 3.31-2.69 6-6 6v-3l-4 4 4 4v-3c4.42 0 8-3.58 8-8 0-1.57-.46-3.03-1.24-4.26z"/>
            </svg>
            <span><?php esc_html_e('Refresh', 'wpfd'); ?></span>
        </li>
        <?php if (wpfd_is_cloud_exists()) : ?>
        <li data-action="folder.synchronize">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000">
                <path d="M0 0h24v24H0V0z" fill="none"/>
                <path d="M17 7h-4v2h4c1.65 0 3 1.35 3 3s-1.35 3-3 3h-4v2h4c2.76 0 5-2.24 5-5s-2.24-5-5-5zm-6 8H7c-1.65 0-3-1.35-3-3s1.35-3 3-3h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-2zm-3-4h8v2H8z"/>
            </svg>
            <span><?php esc_html_e('Synchronize', 'wpfd'); ?></span>
        </li>
        <?php endif; ?>
        <li data-action="folder.delete">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000">
                <path d="M0 0h24v24H0V0z" fill="none"/>
                <path d="M16 9v10H8V9h8m-1.5-6h-5l-1 1H5v2h14V4h-3.5l-1-1zM18 7H6v12c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7z"/>
            </svg>
            <span><?php esc_html_e('Delete', 'wpfd'); ?></span>
        </li>
        <li data-action="folder.copy_shortcode">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000">
                <path d="M0 0h24v24H0V0z" fill="none"/>
                <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
            </svg>
            <span><?php esc_html_e('Copy Shortcode', 'wpfd'); ?></span>
        </li>
        <li class="wpfd-has-submenu" data-action="folder.change_color">
            <svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24"
                 width="24px" fill="#000000">
                <g>
                    <rect fill="none" height="24" width="24"/>
                </g>
                <g>
                    <path d="M16.56,8.94L7.62,0L6.21,1.41l2.38,2.38L3.44,8.94c-0.59,0.59-0.59,1.54,0,2.12l5.5,5.5C9.23,16.85,9.62,17,10,17 s0.77-0.15,1.06-0.44l5.5-5.5C17.15,10.48,17.15,9.53,16.56,8.94z M5.21,10L10,5.21L14.79,10H5.21z M19,11.5c0,0-2,2.17-2,3.5 c0,1.1,0.9,2,2,2s2-0.9,2-2C21,13.67,19,11.5,19,11.5z M2,20h20v4H2V20z"/>
                </g>
            </svg>
            <span><?php esc_html_e('Change color', 'wpfd'); ?></span>
            <ul class="wpfd-subcontext z-depth-1">
                <li>
                    <?php include 'submenu/ui-colors.php'; ?>
                </li>
            </ul>
        </li>
        <li data-action="folder.settings">

            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000">
                <path d="M0 0h24v24H0V0z" fill="none"/>
                <path d="M19.43 12.98c.04-.32.07-.64.07-.98 0-.34-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.09-.16-.26-.25-.44-.25-.06 0-.12.01-.17.03l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.06-.02-.12-.03-.18-.03-.17 0-.34.09-.43.25l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98 0 .33.03.66.07.98l-2.11 1.65c-.19.15-.24.42-.12.64l2 3.46c.09.16.26.25.44.25.06 0 .12-.01.17-.03l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.06.02.12.03.18.03.17 0 .34-.09.43-.25l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zm-1.98-1.71c.04.31.05.52.05.73 0 .21-.02.43-.05.73l-.14 1.13.89.7 1.08.84-.7 1.21-1.27-.51-1.04-.42-.9.68c-.43.32-.84.56-1.25.73l-1.06.43-.16 1.13-.2 1.35h-1.4l-.19-1.35-.16-1.13-1.06-.43c-.43-.18-.83-.41-1.23-.71l-.91-.7-1.06.43-1.27.51-.7-1.21 1.08-.84.89-.7-.14-1.13c-.03-.31-.05-.54-.05-.74s.02-.43.05-.73l.14-1.13-.89-.7-1.08-.84.7-1.21 1.27.51 1.04.42.9-.68c.43-.32.84-.56 1.25-.73l1.06-.43.16-1.13.2-1.35h1.39l.19 1.35.16 1.13 1.06.43c.43.18.83.41 1.23.71l.91.7 1.06-.43 1.27-.51.7 1.21-1.07.85-.89.7.14 1.13zM12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0 6c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
            </svg>
            <span><?php esc_html_e('Edit Category', 'wpfd'); ?></span>
        </li>
    </ul>
</div>