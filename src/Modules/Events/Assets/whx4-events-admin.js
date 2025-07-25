document.addEventListener('DOMContentLoaded', function () {
  const nonce = whx4EventsAjax.nonce;

    /**
     * Send a generic AJAX request to the backend with event instance data.
     */
    function sendAjax(action, postId, date, button) {
        //alert('action: '+action+'; postId: '+postId+'; date: '+date+'; button: '+button);
        console.log( 'action: '+action+'; postId: '+postId+'; date: '+date+'; button: '+button );
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
                const row = button.closest('.whx4-instance-block');
                if (row && response.data?.html) {
                    row.outerHTML = response.data.html;
                } else {
                    console.warn('Missing HTML in AJAX response.');
                }
                //window.location.reload(); // Simple solution; or you could replace just the row
            } else {
                alert(response.data?.message || 'Error');
                button.disabled = false;
                button.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('AJAX error:', error);
            alert('Unexpected error occurred.');
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }

    document.addEventListener('click', function(event) {
        const btn = event.target.closest('button');

        if (!btn || !btn.closest('.whx4-instance-actions')) {
            return;
        }

        const block = btn.closest('.whx4-instance-block');
        if (!block) {
            console.error('Could not find .whx4-instance-block for button:', btn);
        }

        const date = block?.dataset.date;
        const postId = block?.dataset.postId;
        const action = btn.dataset.action;

        if (!action || !date || !postId) {
            console.warn('Missing action/date/postId', { action, date, postId });
            return;
        }

        sendAjax(`whx4_${action}`, postId, date, btn);
    });

});
