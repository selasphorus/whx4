function whx4ShowNotice(message, type = 'error') {
    const notice = $(`
        <div class="notice notice-${type} is-dismissible">
            <p>${message}</p>
        </div>
    `);
    $('#whx4_event_exclusions_box').before(notice);
    notice.find('.notice-dismiss').on('click', function() {
        notice.remove();
    });
}

jQuery(function($) {
    $(document).on('click', '.whx4-create-replacement-btn', function(e) {
        const btn = $(this);
        const li = btn.closest('li');
        const eventId = btn.data('event-id');
        const date = btn.data('date');

        const spinner = $('<span class="spinner" style="float:none; margin-left:5px; vertical-align:middle;"></span>');
        btn.after(spinner);
        btn.prop('disabled', true);

        setTimeout(function() {
            $.post(window.ajaxurl, {
                action: 'whx4_check_replacement',
                event_id: eventId,
                date: date
            }, function(response) {
                spinner.remove();
                if (response.success && response.data.exists) {
                    btn.remove();
                    li.append('<em>Replacement created</em>');
                    whx4ShowNotice('Replacement event created successfully.', 'success');
                } else {
                    btn.prop('disabled', false);
                    whx4ShowNotice('Could not verify replacement. Please try again.', 'error');
                }
            });
        }, 1000);
    });
});
