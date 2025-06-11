document.addEventListener('DOMContentLoaded', function () {
  const nonce = whx4EventsAjax.nonce;

  function sendAjax(action, postId, date, button) {
    button.disabled = true;
    button.textContent = '...';

    fetch(whx4EventsAjax.ajax_url, {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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
        button.textContent = action === 'whx4_exclude_date' ? 'Exclude' : 'Un-exclude';
      }
    });
  }

  document.querySelectorAll('.whx4-exclude-date').forEach(btn => {
    btn.addEventListener('click', e => {
      const date = btn.dataset.date;
      const postId = btn.dataset.postId;
      sendAjax('whx4_exclude_date', postId, date, btn);
    });
  });

  document.querySelectorAll('.whx4-unexclude-date').forEach(btn => {
    btn.addEventListener('click', e => {
      const date = btn.dataset.date;
      const postId = btn.dataset.postId;
      sendAjax('whx4_unexclude_date', postId, date, btn);
    });
  });
});
