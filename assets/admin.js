(function () {
    'use strict';

    const input = document.getElementById('turnsite-secret');
    const button = document.querySelector('.wp-turnsite-secret-toggle');

    if (!input || !button || typeof wpTurnSiteAdmin === 'undefined') {
        return;
    }

    const icon = button.querySelector('.dashicons');

    function setVisibility(visible) {
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-label', visible ? wpTurnSiteAdmin.hideLabel : wpTurnSiteAdmin.revealLabel);
        button.dataset.revealed = visible ? '1' : '0';
        if (icon) {
            icon.classList.toggle('dashicons-visibility', !visible);
            icon.classList.toggle('dashicons-hidden', visible);
        }
    }

    button.addEventListener('click', async function () {
        if (button.dataset.revealed === '1') {
            setVisibility(false);
            return;
        }

        if (input.dataset.secretConfigured !== '1' || input.dataset.secretLoaded === '1') {
            setVisibility(true);
            return;
        }

        button.disabled = true;
        const body = new URLSearchParams({
            action: 'wp_turnsite_reveal_secret',
            _ajax_nonce: wpTurnSiteAdmin.nonce,
        });

        try {
            const response = await fetch(wpTurnSiteAdmin.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: body.toString(),
            });
            const payload = await response.json();
            if (!response.ok || !payload.success || !payload.data.secret) {
                throw new Error('Secret unavailable');
            }

            input.value = payload.data.secret;
            input.dataset.secretLoaded = '1';
            setVisibility(true);
        } catch (error) {
            window.alert(wpTurnSiteAdmin.errorMessage);
        } finally {
            button.disabled = false;
        }
    });
}());
