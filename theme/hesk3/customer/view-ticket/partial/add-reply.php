<?php
// This guard is used to ensure that users can't hit this outside of actual HESK code
if (!defined('IN_SCRIPT')) {
    die();
}

function showReplyForm($trackingId, $email, $reopen) {
    global $hesk_settings, $hesklang;
    ?>
    <article class="ticket__body_block">
        <div class="text-bold"><?php echo $hesklang['add_reply']; ?></div>
        <form method="post" action="reply_ticket.php" aria-label="<?php echo $hesklang['add_a_ticket_reply']; ?>" class="form form--reply" enctype="multipart/form-data" onsubmit="<?php if ($hesk_settings['submitting_wait']): ?>hesk_showLoadingMessage('recaptcha-submit');<?php endif; ?>" novalidate>
            <div class="form-group required">
                <label class="label" for="message"><?php echo $hesklang['message']; ?></label>
                <textarea id="message" name="message" class="form-control" required><?php if (isset($_SESSION['ticket_message'])) {
                        echo stripslashes(hesk_input($_SESSION['ticket_message']));
                    } ?></textarea>
            </div>
            <?php
            /* attachments */
            if ($hesk_settings['attachments']['use']) {
                require_once(TEMPLATE_PATH . 'customer/util/attachments.php');
                ?>
                <section class="param param--attach">
                    <span class="label"><?php echo $hesklang['attachments']; ?></span>
                    <div class="attach">
                        <?php hesk3_output_drag_and_drop_attachment_holder(); ?>
                        <div class="attach-tooltype">
                            <a class="link" href="file_limits.php" onclick="HESK_FUNCTIONS.openWindow('file_limits.php',250,500);return false;">
                                <?php echo $hesklang['ful']; ?>
                            </a>
                        </div>
                    </div>
                </section>
                <?php
            }
            ?>
            <section class="form__submit">
                <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>">
                <input type="hidden" name="orig_track" value="<?php echo $trackingId; ?>">
                <button type="submit" class="btn btn-full" id="recaptcha-submit"
                        ripple="ripple"><?php echo $hesklang['submit_reply']; ?></button>
                <?php
                if ($hesk_settings['email_view_ticket']) {
                    echo '<input type="hidden" name="e" value="' . $email . '">';
                }
                if ($reopen) {
                    echo '<input type="hidden" name="reopen" value="1">';
                }
                ?>
            </section>
        </form>
        <div id="loading-overlay" class="loading-overlay">
            <div id="loading-message" class="loading-message">
                <div class="spinner"></div>
                <p><?php echo $hesklang['sending_wait']; ?></p>
            </div>
        </div>
    </article>
    <?php
}
