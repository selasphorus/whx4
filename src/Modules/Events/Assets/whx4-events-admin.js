document.addEventListener('DOMContentLoaded', function () {
  const nonce = whx4EventsAjax.nonce;

    /**
     * Send a generic AJAX request to the backend with event instance data.
     */
    function sendAjax(action, postId, date, button) {
        //if ( action == 'whx4_create_replacement' ) {
            alert('action: '+action+'; postId: '+postId+'; date: '+date+'; button: '+button);
        //}
        //
        button.disabled = true;

        //const originalText = button.textContent;
        //button.textContent = '...';
        const originalText = btn.innerHTML;
        btn.innerHTML = '\u23F3'; // ⏳ Hourglass emoji

        fetch(whx4EventsAjax.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action,
                nonce,
                post_id: postId,
                date
            })
        })
        .then(r => r.json())
        .then(response => {
            if (response.success) {
                window.location.reload(); // Simple solution; or you could replace just the row
            } else {
                alert(response.data?.message || 'Error');
                button.disabled = false;
                //button.textContent = action === 'whx4_exclude_date' ? 'Exclude' : 'Un-exclude'; // TODO: replace text with img file links -- see EventInstances.php
            }
        });
    }

    document.addEventListener('click', function(event) {
        const btn = event.target.closest('button');

        // Only respond if the button is inside a .rex-instance-actions container
        if (!btn || !btn.closest('.whx4-instance-actions')) {
            return;
        }

        const action = btn.dataset.action;
        const date = btn.dataset.date;
        const postId = btn.dataset.postId;

        if (!action || !date || !postId) {
            return;
        }

        sendAjax('whx4_${action}', postId, date, btn);

        // Prevent double-click
        //btn.disabled = true;

        // Optional: show loading state
        //const originalText = btn.innerHTML;
        //btn.innerHTML = '\u23F3'; // ⏳ Hourglass emoji

        /*sendAjax(`whx4_${action}`, postId, date, btn)
            .finally(() => {
                // Restore button state
                btn.disabled = false;
                //btn.innerHTML = originalText;
            });*/
    });

    /*
    // Attach handlers for excluding a date
    document.querySelectorAll('.whx4-exclude-date').forEach(btn => {
        btn.addEventListener('click', e => {
            const date = btn.dataset.date;
            const postId = btn.dataset.postId;
            //alert('postId:'+postId+': date: '+date);
            sendAjax('whx4_exclude_date', postId, date, btn);
        });
    });
    */
});
