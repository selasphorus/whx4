document.addEventListener('DOMContentLoaded', function () {
  const nonce = whx4EventsAjax.nonce;

    /**
     * Send a generic AJAX request to the backend with event instance data.
     */
    function sendAjax(action, postId, date, button) {
        //alert('action: '+action+'; postId: '+postId+'; date: '+date+'; button: '+button);
        //
        button.disabled = true;

        const originalText = button.innerHTML;
        button.innerHTML = '\u23F3'; // Hourglass emoji

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
                button.innerHTML = originalText;
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

        sendAjax(`whx4_${action}`, postId, date, btn);
    });

});
