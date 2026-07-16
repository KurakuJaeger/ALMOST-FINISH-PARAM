<?php
require_once __DIR__ . '/../../src/middleware/authentication.php';
require_once __DIR__ . '/includes/db.php';

ensureSessionStarted();
$currentUser = currentUser();
$isCustomer = $currentUser && $currentUser['status'] === 'active'
    && $currentUser['role_name'] === 'Customer';
$supportNotice = $_SESSION['support_notice'] ?? null;
unset($_SESSION['support_notice']);
$customerOrders = [];
$customerConcerns = [];

if ($isCustomer) {
    $orderStatement = $pdo->prepare(
        'SELECT order_id, order_status, total_amount, created_at
         FROM orders
         WHERE user_id = :user_id
         ORDER BY created_at DESC'
    );
    $orderStatement->execute(['user_id' => $currentUser['user_id']]);
    $customerOrders = $orderStatement->fetchAll();

    $concernStatement = $pdo->prepare(
        'SELECT concern_id, order_id, subject, message, response, status,
                created_at, updated_at
         FROM support_concerns
         WHERE customer_id = :user_id
         ORDER BY created_at DESC'
    );
    $concernStatement->execute(['user_id' => $currentUser['user_id']]);
    $customerConcerns = $concernStatement->fetchAll();
}

// Contact Details 
$pageTitle         = "Contact Us";
$brandName         = "Param";

//Store Info
$storeAddress      = "123 P. Paredes Street in Sampaloc, Manila";
$storePhoneDisplay = "+0123-456-789";
$storePhoneRaw     = "+0123456789";
$storeEmail        = "param@gmail.com";

// Store Working Hours
$hoursWeekday      = "10:00 - 20:00";
$hoursWeekend      = "11:00 - 18:00";

// Hiring 
$isHiring          = true;
$hiringTitle       = "We're Hiring!";
$hiringText        = "Interested in joining the Param team? We are looking for passionate individuals to grow with us. Fill out the application form below to submit your CV and portfolio.";

// Social Links 
$socialLinks = [
    'facebook'  => ['url' => '#', 'icon' => 'fab fa-facebook-f', 'label' => 'Facebook'],
    'youtube'   => ['url' => '#', 'icon' => 'fab fa-youtube',    'label' => 'YouTube'],
    'twitter'   => ['url' => '#', 'icon' => 'fab fa-twitter',    'label' => 'Twitter'],
    'instagram' => ['url' => '#', 'icon' => 'fab fa-instagram',  'label' => 'Instagram'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo$brandName; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?= (int) filemtime(__DIR__ . '/css/style.css') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(appUrl('store/css/ContactUs.css') . '?v=' . filemtime(__DIR__ . '/css/ContactUs.css')) ?>">
</head>
<body>

    <?php 
    $path = ''; 
    include 'includes/header.php'; 
    ?>

    <main class="container py-5 support-page">
        <div class="card border-0 shadow-sm overflow-hidden support-main-card">
            <div class="row g-0">
                
                <div class="col-lg-7 p-4 p-xl-5 bg-white">
                    <span class="support-eyebrow d-block text-uppercase fw-bold mb-2">Customer assistance</span>
                    <h1 class="h3 support-title fw-bold mb-2">Contact Customer Service</h1>
                    <p class="text-secondary lh-base mb-4">Tell us what happened. Choose the related order so the support team has the right context.</p>

                    <?php if (is_array($supportNotice)): ?>
                        <div class="alert <?= $supportNotice['type'] === 'success' ? 'alert-success' : 'alert-danger' ?> mb-4" role="status">
                            <?= htmlspecialchars((string) $supportNotice['message']) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($isCustomer): ?>
                    <form action="<?= htmlspecialchars(appUrl('store/contact_process.php')) ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold">Your Name <span aria-hidden="true">*</span></label>
                                <input type="text" class="form-control form-control-lg support-control" id="name" value="<?= htmlspecialchars(trim($currentUser['first_name'] . ' ' . $currentUser['last_name'])) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold">Email <span aria-hidden="true">*</span></label>
                                <input type="email" class="form-control form-control-lg support-control" id="email" value="<?= htmlspecialchars($currentUser['email']) ?>" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="order_id" class="form-label fw-semibold">Related Order <?= $customerOrders ? '<span aria-hidden="true">*</span>' : '' ?></label>
                            <select class="form-select form-select-lg support-control" id="order_id" name="order_id">
                                <?php foreach ($customerOrders as $order): ?>
                                    <option value="<?= (int) $order['order_id'] ?>">
                                        Order #<?= (int) $order['order_id'] ?> &middot; <?= htmlspecialchars(ucwords(str_replace('_', ' ', $order['order_status']))) ?> &middot; &#8369;<?= number_format((float) $order['total_amount'], 2) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="">General concern (not related to an order)</option>
                            </select>
                            <div class="form-text lh-base">
                                <?= $customerOrders
                                    ? 'Your latest order is selected automatically. Choose General concern only when no order is involved.'
                                    : 'You have no orders yet, so this will be submitted as a general concern.' ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label fw-semibold">Subject <span aria-hidden="true">*</span></label>
                            <input type="text" class="form-control form-control-lg support-control" id="subject" name="subject" placeholder="Briefly describe the concern" required>
                        </div>

                        <div class="mb-4">
                            <label for="message" class="form-label fw-semibold">Your Message <span aria-hidden="true">*</span></label>
                            <textarea class="form-control support-control" id="message" name="message" rows="5" placeholder="Include the details Customer Service should know" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-param support-submit-btn px-4 py-3 fw-semibold">Submit Support Request</button>
                    </form>
                    <?php else: ?>
                        <div class="alert support-login-prompt p-4 mb-0">
                            <h2 class="h5 fw-bold">Sign in to contact Customer Service</h2>
                            <p class="mb-3">Your verified account lets us securely connect a concern to your order and show you the response.</p>
                            <a class="btn btn-param px-4 py-2" href="<?= htmlspecialchars(appUrl('login')) ?>">Log in to continue</a>
                        </div>
                    <?php endif; ?>
                </div>

                <aside class="col-lg-5 p-4 p-xl-5 d-flex flex-column justify-content-center info-section" aria-label="Store contact information">
                    
                    <div class="mb-4">
                        <h2 class="h6 text-uppercase fw-bold info-block-title">Address</h2>
                        <p class="mb-0 lh-base"><?php echo htmlspecialchars($storeAddress); ?></p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h6 text-uppercase fw-bold info-block-title">Contact</h2>
                        <p class="mb-0 lh-base info-text">
                            <strong>Phone:</strong> <a href="tel:<?php echo htmlspecialchars($storePhoneRaw); ?>"><?php echo htmlspecialchars($storePhoneDisplay); ?></a><br>
                            <strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($storeEmail); ?>"><?php echo htmlspecialchars($storeEmail); ?></a>
                        </p>
                    </div>

                    <div class="mb-4">
                        <h2 class="h6 text-uppercase fw-bold info-block-title">Open Time</h2>
                        <p class="mb-0 lh-base">
                            Monday - Friday : <?php echo htmlspecialchars($hoursWeekday); ?><br>
                            Saturday - Sunday : <?php echo htmlspecialchars($hoursWeekend); ?>
                        </p>
                    </div>

                    <div>
                        <h2 class="h6 text-uppercase fw-bold info-block-title">Stay Connected</h2>
                        <div class="d-flex gap-2 mt-3">
                            <?php foreach ($socialLinks as$social): ?>
                                <a href="<?php echo htmlspecialchars($social['url']); ?>" class="social-icon" aria-label="<?php echo htmlspecialchars($social['label']); ?>">
                                    <i class="<?php echo htmlspecialchars($social['icon']); ?>"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </aside>

            </div>
        </div>

        <?php if ($isCustomer): ?>
            <section class="card border-0 shadow-sm mt-4 p-4 p-lg-5 support-history" id="my-support-requests">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
                    <div>
                        <span class="support-eyebrow d-block text-uppercase fw-bold mb-1">Track conversations</span>
                        <h2 class="h4 support-title fw-bold mb-0">My Support Requests</h2>
                    </div>
                    <span class="badge rounded-pill support-count align-self-start align-self-sm-center"><?= count($customerConcerns) ?> request<?= count($customerConcerns) === 1 ? '' : 's' ?></span>
                </div>

                <?php if (!$customerConcerns): ?>
                    <div class="border rounded-3 bg-light text-center p-4 p-md-5">
                        <strong class="d-block support-title mb-1">No support requests yet</strong>
                        <span class="text-secondary">Requests you submit above and replies from Customer Service will appear here.</span>
                    </div>
                <?php else: ?>
                    <div class="support-request-list">
                        <?php foreach ($customerConcerns as $concern): ?>
                            <article class="card border support-request-card">
                                <div class="card-body p-4">
                                <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mb-2">
                                    <h3 class="h6 support-title fw-bold mb-0">#<?= (int) $concern['concern_id'] ?> &middot; <?= htmlspecialchars($concern['subject']) ?></h3>
                                    <span class="badge rounded-pill support-status support-status-<?= htmlspecialchars($concern['status']) ?> align-self-start"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $concern['status']))) ?></span>
                                </div>
                                <p class="support-request-meta">
                                    <?= $concern['order_id'] ? 'Order #' . (int) $concern['order_id'] : 'General concern' ?> &middot; Submitted <?= htmlspecialchars($concern['created_at']) ?>
                                </p>
                                <p class="mb-3 lh-base"><?= nl2br(htmlspecialchars($concern['message'])) ?></p>
                                <div class="support-response rounded-end-3">
                                    <strong>Customer Service response</strong>
                                    <p><?= $concern['response'] ? nl2br(htmlspecialchars($concern['response'])) : 'Waiting for a response.' ?></p>
                                </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($isHiring): ?>
            <div class="card hiring-card mt-4 shadow-sm p-4 p-lg-5">
                <div class="hiring-header mb-3">
                    <div class="hiring-title">
                        <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($hiringTitle); ?>
                        <span class="hiring-badge">Open Positions</span>
                    </div>
                    <p class="hiring-text mt-2"><?php echo htmlspecialchars($hiringText); ?></p>
                </div>

                <hr class="hiring-divider">

                <div class="text-end">
                    <a class="btn btn-param" href="<?= htmlspecialchars(appUrl('apply')) ?>">
                        Open the Staff Application Portal <i class="fas fa-arrow-up-right-from-square ms-1"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php 
$path = ''; 
include 'includes/footer.php'; 
?>
