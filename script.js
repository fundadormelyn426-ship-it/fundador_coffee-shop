const $ = (s) => document.querySelector(s);
const $$ = (s) => document.querySelectorAll(s);

let cart = [];

// ==============================
// Toast Notification
// ==============================

const toast = (message) => {
    const t = $('#toast');

    if (!t) return;

    t.textContent = message;
    t.classList.add('show');

    clearTimeout(window.tt);

    window.tt = setTimeout(() => {
        t.classList.remove('show');
    }, 2200);
};

// ==============================
// Mobile Navigation
// ==============================

$('#hamb')?.addEventListener('click', () => {
    $('#nav')?.classList.toggle('open');
});

$$('#nav a').forEach((a) => {
    a.addEventListener('click', () => {
        $('#nav')?.classList.remove('open');
    });
});

// ==============================
// API Helper
// ==============================

async function api(action, data = {}) {
    try {
        const body = new URLSearchParams({
            action,
            ...data
        });

        const response = await fetch('api.php', {
            method: 'POST',
            body
        });

        return await response.json();

    } catch (error) {

        return {
            success: false,
            message:
                'Unable to connect to the server. Make sure Apache and MySQL are running.'
        };
    }
}

// ==============================
// Load Cart
// ==============================

async function loadCart() {
    const response = await api('cart');

    if (response.success) {
        cart = response.data.items;
        render();
    }
}

// ==============================
// Render Cart
// ==============================

function render() {
    const box = $('#items');

    let total = 0;
    let count = 0;

    if (!box) return;

    // Empty cart
    if (!cart.length) {

        box.innerHTML = '<p>Your order is empty.</p>';

    } else {

        box.innerHTML = cart.map((item) => {

            total += item.price * item.qty;
            count += item.qty;

            return `
                <div class="cart-row">

                    <div>
                        <b>${escapeHtml(item.name)}</b>

                        <small>
                            ₱${item.price.toFixed(2)} × ${item.qty}
                            <br>
                            ${item.stock} available
                        </small>
                    </div>

                    <div>

                        <b>
                            ₱${(item.price * item.qty).toFixed(2)}
                        </b>

                        <br>

                        <button
                            class="qty"
                            data-id="${item.id}"
                            data-qty="${item.qty - 1}"
                            type="button"
                        >
                            −
                        </button>

                        <button
                            class="qty"
                            data-id="${item.id}"
                            data-qty="${item.qty + 1}"
                            type="button"
                        >
                            +
                        </button>

                        <button
                            class="remove"
                            data-id="${item.id}"
                            type="button"
                        >
                            Remove
                        </button>

                    </div>

                </div>
            `;

        }).join('');
    }

    // ==========================
    // Remove Item
    // ==========================

    $$('.remove').forEach((button) => {

        button.onclick = async () => {

            const response = await api('remove', {
                product_id: button.dataset.id
            });

            if (response.success) {

                cart = response.data.items;

                render();

                toast('Item removed');

            } else {

                toast(response.message);
            }
        };
    });

    // ==========================
    // Update Quantity
    // ==========================

    $$('.qty').forEach((button) => {

        button.onclick = async () => {

            const response = await api('update', {
                product_id: button.dataset.id,
                qty: button.dataset.qty
            });

            if (response.success) {

                cart = response.data.items;

                render();

            } else {

                toast(response.message);
            }
        };
    });

    // ==========================
    // Cart Total
    // ==========================

    const totalElement = $('#total');
    const countElement = $('#count');

    if (totalElement) {
        totalElement.textContent =
            '₱' +
            total.toLocaleString(undefined, {
                minimumFractionDigits: 2
            });
    }

    if (countElement) {
        countElement.textContent = count;
    }
}

// ==============================
// Escape HTML
// ==============================

function escapeHtml(value) {

    const div = document.createElement('div');

    div.textContent = value;

    return div.innerHTML;
}

// ==============================
// Add To Cart
// ==============================

$$('.add').forEach((button) => {

    button.addEventListener('click', async () => {

        if (button.disabled) return;

        button.disabled = true;

        const oldText = button.innerHTML;

        button.innerHTML = 'Adding…';

        const response = await api('add', {
            product_id: button.dataset.id,
            qty: 1
        });

        button.disabled = false;

        button.innerHTML = oldText;

        if (response.success) {

            cart = response.data.items;

            render();

            toast('Added to your order');

            $('#cart')?.classList.add('open');

        } else {

            toast(response.message);
        }
    });
});

// ==============================
// Open / Close Cart
// ==============================

$('#cartOpen')?.addEventListener('click', () => {
    $('#cart')?.classList.add('open');
});

$('#close')?.addEventListener('click', () => {
    $('#cart')?.classList.remove('open');
});

// ==============================
// Escape Key
// ==============================

document.addEventListener('keydown', (event) => {

    if (event.key === 'Escape') {

        $('#cart')?.classList.remove('open');

        closeLightbox();
    }
});

// ==============================
// Social Links
// ==============================

document.addEventListener('click', (event) => {

    const social = event.target.closest('[data-social]');

    if (!social) return;

    const url = social.dataset.url;

    if (url) {

        window.open(
            url,
            '_blank',
            'noopener,noreferrer'
        );

    } else {

        toast(
            `${social.dataset.social || 'Social'} link is not configured.`
        );
    }
});

// ==============================
// Checkout
// ==============================

$('#checkout')?.addEventListener('click', async () => {

    if (!cart.length) {

        return toast('Please add an item first.');
    }

    const name = $('#customerName')?.value.trim();
    const email = $('#customerEmail')?.value.trim();

    // Validate name and email
    if (
        !name ||
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
    ) {

        return toast(
            'Please enter a valid name and email.'
        );
    }

    const button = $('#checkout');

    button.disabled = true;
    button.textContent = 'Processing…';

    const response = await api('checkout', {
        name,
        email
    });

    if (response.success) {

        location.href =
            'success.php?id=' +
            encodeURIComponent(response.data.order_id);

    } else {

        button.disabled = false;

        button.textContent = 'Place Order';

        toast(response.message);

        await loadCart();
    }
});

// ==============================
// Gallery Tabs
// ==============================

$$('.tabs button').forEach((button) => {

    button.addEventListener('click', () => {

        $$('.tabs button').forEach((item) => {
            item.classList.remove('selected');
        });

        button.classList.add('selected');

        const filter = button.dataset.filter;

        $$('.gallery-grid img').forEach((image) => {

            image.classList.toggle(
                'hide',
                filter !== 'all' &&
                image.dataset.type !== filter
            );
        });
    });
});

// ==============================
// Lightbox
// ==============================

const lightbox = $('#lightbox');
const lightboxImg = $('#lightboxImg');

function openLightbox(src, alt) {

    if (!lightbox || !lightboxImg) return;

    lightboxImg.src = src;

    lightboxImg.alt =
        alt || 'Gallery image';

    lightbox.classList.add('open');

    lightbox.setAttribute(
        'aria-hidden',
        'false'
    );
}

function closeLightbox() {

    if (!lightbox) return;

    lightbox.classList.remove('open');

    lightbox.setAttribute(
        'aria-hidden',
        'true'
    );
}

// Gallery image click
$$('.gallery-grid img').forEach((image) => {

    image.addEventListener('click', () => {

        openLightbox(
            image.src,
            image.alt
        );
    });
});

// Close lightbox button
$('#lightboxClose')?.addEventListener(
    'click',
    closeLightbox
);

// Click outside image
lightbox?.addEventListener('click', (event) => {

    if (event.target === lightbox) {
        closeLightbox();
    }
});

// ==============================
// Full Gallery
// ==============================

$('#fullGallery')?.addEventListener('click', () => {

    // Select "All"
    $$('.tabs button').forEach((button) => {
        button.classList.remove('selected');
    });

    $('.tabs button[data-filter="all"]')
        ?.classList.add('selected');

    // Show all images
    $$('.gallery-grid img').forEach((image) => {
        image.classList.remove('hide');
    });

    // Scroll to gallery
    document
        .querySelector('#gallery')
        ?.scrollIntoView({
            behavior: 'smooth'
        });

    toast(
        'Full gallery opened — click any photo to enlarge.'
    );
});

// ==============================
// Smooth In-Page Navigation
// ==============================

$$('a[href^="#"]').forEach((link) => {

    link.addEventListener('click', (event) => {

        const id = link.getAttribute('href');

        if (
            id &&
            id.length > 1 &&
            $(id)
        ) {

            event.preventDefault();

            $(id).scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// ==============================
// Start Application
// ==============================

loadCart();