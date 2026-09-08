<?php
require_once "config.php";

$products = $pdo
    ->query("SELECT * FROM products WHERE is_active = 1 ORDER BY id")
    ->fetchAll();
?>

<!doctype html>

<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

```
<title>Fundador Coffee Shop</title>

<meta
    name="description"
    content="Fundador Coffee Shop — freshly brewed coffee, pastries and warm moments."
>

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
    rel="stylesheet"
>

<!-- Main CSS -->
<link rel="stylesheet" href="style.css">
```

</head>

<body>

<!-- =========================================================
     HEADER / NAVIGATION
========================================================= -->

<header class="header">
    <div class="nav">

```
    <a class="logo" href="#home">
        <span class="cup">☕</span>

        <span>
            <b>FUNDADOR</b>
            <small>COFFEE SHOP</small>
            <i>— EST 2026 —</i>
        </span>
    </a>

    <button class="hamb" id="hamb" type="button">
        ☰
    </button>

    <nav id="nav">

        <a class="active" href="#home">Home</a>
        <a href="#menu">Menu</a>
        <a href="#about">About Us</a>
        <a href="#gallery">Gallery</a>
        <a href="#contact">Contact</a>

        <?php if (isset($_SESSION["user_id"])): ?>

            <a href="logout.php">Logout</a>

        <?php else: ?>

            <a href="login.php">Login</a>
            <a href="register.php">Register</a>

        <?php endif; ?>

        <a class="order-link" href="#order">
            ☕ &nbsp; Order Now
        </a>

    </nav>

</div>
```

</header>

<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main>

```
<!-- =====================================================
     HERO
====================================================== -->
<section id="home" class="hero">

    <div class="hero-bg"></div>
    <div class="hero-shade"></div>

    <div class="hero-inner">

        <p class="eyebrow">
            GOOD COFFEE. GOOD MOOD.
        </p>

        <h1>
            Freshly Brewed.<br>
            Made With <em>Passion.</em>
        </h1>

        <p class="lead">
            Handcrafted coffee, delicious pastries, and<br>
            warm moments made especially for you.
        </p>

        <div class="buttons">

            <a class="btn green" href="#order">
                ☕ &nbsp; Order Now
            </a>

            <a class="btn outline" href="#menu">
                Explore Menu
            </a>

        </div>

    </div>

    <div class="dots">
        <span class="on"></span>
        <span></span>
        <span></span>
    </div>

</section>


<!-- =====================================================
     BEST SELLERS / MENU
====================================================== -->
<section class="sellers" id="menu">

    <div class="wrap">

        <div class="heading">

            <span></span>

            <div>
                <h2>OUR BEST SELLERS</h2>
                <p>Customer favorites, made with love.</p>
            </div>

            <span></span>

        </div>


        <div class="cards">

            <?php foreach ($products as $product): ?>

                <article class="card">

                    <img
                        src="<?= h($product['image']) ?>"
                        alt="<?= h($product['name']) ?>"
                    >

                    <div>

                        <h3>
                            <?= h($product['name']) ?>
                        </h3>

                        <p>
                            <?= h($product['description']) ?>
                        </p>

                        <b>
                            ₱<?= number_format($product['price'], 2) ?>
                        </b>


                        <?php if ((int) $product['stock'] > 0): ?>

                            <small class="stock-label">
                                <?= (int) $product['stock'] ?> left in stock
                            </small>

                            <button
                                class="add"
                                data-id="<?= $product['id'] ?>"
                                type="button"
                            >
                                🛒 &nbsp; Add to Order
                            </button>

                        <?php else: ?>

                            <small class="stock-label out">
                                Out of Stock
                            </small>

                            <button
                                class="add"
                                disabled
                                type="button"
                            >
                                Out of Stock
                            </button>

                        <?php endif; ?>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    </div>

</section>


<!-- =====================================================
     ABOUT US
====================================================== -->
<section class="about" id="about">

    <div class="about-img">

        <img
            src="assets/about.jpg"
            alt="Fundador Coffee Shop"
        >

    </div>


    <div class="about-copy">

        <p class="eyebrow brown">
            ABOUT US
        </p>

        <h2>
            More Than Just Coffee
        </h2>

        <p>
            Fundador Coffee Shop is committed to serving premium-quality
            coffee and handcrafted beverages in a warm and welcoming
            environment. We strive to create meaningful moments by
            delivering exceptional customer service, locally inspired
            flavors and passion in every cup we serve.
        </p>


        <div class="features">

            <div>
                <span>☕</span>
                <b>Premium Coffee</b>
                <small>
                    Sourced from<br>
                    quality beans
                </small>
            </div>

            <div>
                <span>🥐</span>
                <b>Freshly Baked</b>
                <small>
                    Pastries made<br>
                    with love
                </small>
            </div>

            <div>
                <span>♡</span>
                <b>Made With Passion</b>
                <small>
                    In every cup<br>
                    and every bite
                </small>
            </div>

            <div>
                <span>🍃</span>
                <b>Warm &amp; Welcoming</b>
                <small>
                    A cozy place for<br>
                    everyone
                </small>
            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     GALLERY
====================================================== -->
<section class="gallery" id="gallery">

    <div class="wrap">

        <div class="heading">

            <span></span>

            <div>
                <h2>OUR MENU &amp; GALLERY</h2>
                <p>
                    Take a look at our drinks, pastries and cozy space.
                </p>
            </div>

            <span></span>

        </div>


        <!-- Gallery Filters -->
        <div class="tabs">

            <button
                class="selected"
                data-filter="all"
                type="button"
            >
                Coffee
            </button>

            <button
                data-filter="iced"
                type="button"
            >
                Iced Coffee
            </button>

            <button
                data-filter="pastry"
                type="button"
            >
                Pastries
            </button>

            <button
                data-filter="dessert"
                type="button"
            >
                Desserts
            </button>

            <button
                data-filter="noncoffee"
                type="button"
            >
                Non-Coffee
            </button>

        </div>


        <!-- Gallery Images -->
        <div class="gallery-grid">

            <img
                data-type="coffee"
                src="assets/latte.jpg"
                alt="Signature Latte"
            >

            <img
                data-type="iced"
                src="assets/iced-latte.jpg"
                alt="Iced Caramel Latte"
            >

            <img
                data-type="pastry"
                src="assets/croissant.jpg"
                alt="Butter Croissant"
            >

            <img
                class="wide"
                data-type="coffee"
                src="assets/gallery-cafe.jpg"
                alt="Coffee Shop"
            >

            <img
                data-type="coffee"
                src="assets/gallery-cup.jpg"
                alt="Coffee Cup"
            >

            <img
                data-type="coffee"
                src="assets/gallery-table.jpg"
                alt="Coffee Shop Table"
            >

            <img
                data-type="coffee"
                src="assets/gallery-counter.jpg"
                alt="Coffee Shop Counter"
            >

        </div>


        <button
            class="full-gallery"
            id="fullGallery"
            type="button"
        >
            ▣ &nbsp; View Full Menu &amp; Gallery
        </button>

    </div>

</section>


<!-- =====================================================
     SPECIAL OFFERS
====================================================== -->
<section class="offers">

    <div class="offers-box">

        <div class="heading small">

            <span></span>

            <div>
                <h2>SPECIAL OFFERS</h2>
                <p>Great coffee. Greater deals.</p>
            </div>

            <span></span>

        </div>


        <div class="offer-grid">

            <!-- Morning Deal -->
            <div class="offer">

                <div>

                    <label>
                        WEEKDAYS DEAL
                    </label>

                    <h3>
                        10% OFF
                    </h3>

                    <p>
                        Monday to Friday<br>
                        7:00 AM – 11:00 AM
                    </p>

                </div>

                <img
                    src="assets/offer-morning.jpg"
                    alt="Weekdays Deal"
                >

            </div>


            <!-- Pastry Bundle -->
            <div class="offer">

                <div>

                    <label>
                        SWEET BUNDLE
                    </label>

                    <h3>
                        BUY 1<br>
                        GET 1 <small>50% OFF</small>
                    </h3>

                    <p>
                        On all pastries
                    </p>

                </div>

                <img
                    src="assets/offer-pastry.jpg"
                    alt="Sweet Bundle"
                >

            </div>


            <!-- Student Discount -->
            <div class="offer">

                <div>

                    <label>
                        STUDENT DISCOUNT
                    </label>

                    <h3>
                        15% OFF
                    </h3>

                    <p>
                        All Orders<br>
                        Just show your valid ID.
                    </p>

                </div>

                <img
                    src="assets/offer-student.jpg"
                    alt="Student Discount"
                >

            </div>


            <!-- Social Media -->
            <div class="social">

                <span>☕</span>

                <h3>
                    Stay Updated!
                </h3>

                <p>
                    Follow us on our social<br>
                    media for more promos<br>
                    and limited offers.
                </p>


                <div class="social-links">

                    <button
                        type="button"
                        data-social="Facebook"
                        data-url="https://www.facebook.com/"
                    >
                        f
                    </button>

                    <button
                        type="button"
                        data-social="Instagram"
                        data-url="https://www.instagram.com/"
                    >
                        ◎
                    </button>

                    <button
                        type="button"
                        data-social="TikTok"
                        data-url="https://www.tiktok.com/"
                    >
                        ♪
                    </button>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     FOOTER / CONTACT
====================================================== -->
<section class="footer" id="contact">

    <div class="footer-grid">

        <!-- Customer Review -->
        <div class="review">

            <label>
                WHAT OUR CUSTOMERS SAY
            </label>

            <div>
                ★★★★★
            </div>

            <p>
                “The coffee is smooth, delicious,<br>
                and the place feels so welcoming.<br>
                I come here everyday!”
            </p>

            <b>
                — Maria S.
            </b>

        </div>


        <!-- Order CTA -->
        <div class="cta" id="order">

            <h2>
                Your Perfect Cup<br>
                Is Waiting.
            </h2>

            <p>
                Visit Fundador Coffee Shop and<br>
                enjoy coffee made with passion.
            </p>

            <a
                class="btn green"
                href="#menu"
            >
                ☕ &nbsp; Order Now
            </a>

        </div>


        <!-- Contact -->
        <div class="contact">

            <label>
                CONTACT US
            </label>

            <p>
                📍 Near in Daro, Dumaguete City
            </p>

            <p>
                ☎ +63 912 345 6789
            </p>

            <p>
                ✉ hello@fundadorcoffeeshop.com
            </p>

            <p>
                ◷ Mon - Sun &nbsp;|&nbsp; 7:00 AM - 9:00 PM
            </p>


            <div class="social-links footer-social">

                <button
                    type="button"
                    data-social="Facebook"
                    data-url="https://www.facebook.com/"
                >
                    f
                </button>

                <button
                    type="button"
                    data-social="Instagram"
                    data-url="https://www.instagram.com/"
                >
                    ◎
                </button>

                <button
                    type="button"
                    data-social="TikTok"
                    data-url="https://www.tiktok.com/"
                >
                    ♪
                </button>

            </div>

        </div>

    </div>


    <div class="copyright">
        © 2026 Fundador Coffee Shop. All Rights Reserved.
    </div>

</section>
```

</main>

<!-- =========================================================
     LIGHTBOX
========================================================= -->

<div
    class="lightbox"
    id="lightbox"
    aria-hidden="true"
>

```
<button
    id="lightboxClose"
    type="button"
>
    ×
</button>

<img
    id="lightboxImg"
    src=""
    alt=""
>
```

</div>

<!-- =========================================================
     CART
========================================================= -->

<div class="cart" id="cart">

```
<div class="cart-head">

    <h2>
        Your Order
    </h2>

    <button
        id="close"
        type="button"
    >
        ×
    </button>

</div>


<div id="items">

    <p>
        Your order is empty.
    </p>

</div>


<div class="total">

    <b>
        Total
    </b>

    <b id="total">
        ₱0
    </b>

</div>


<!-- Checkout Form -->
<div
    id="checkoutForm"
    class="checkout-form"
>

    <input
        id="customerName"
        placeholder="Full name"
    >

    <input
        id="customerEmail"
        type="email"
        placeholder="Email address"
    >

    <button
        id="checkout"
        type="button"
    >
        Place Order
    </button>

</div>
```

</div>

<!-- =========================================================
     FLOATING CART BUTTON
========================================================= -->

<button
class="cart-float"
id="cartOpen"
type="button"

>

```
🛒
```

```
<span id="count">0</span>
```

</button>

<!-- =========================================================
     TOAST MESSAGE
========================================================= -->

<div
    class="toast"
    id="toast"
></div>

<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script src="script.js"></script>

</body>
</html>
