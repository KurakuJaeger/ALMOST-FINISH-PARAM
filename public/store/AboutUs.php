<?php
session_start();
require_once 'includes/db.php';
$pageTitle = "About Us - Param";

// Team Members 
$teamMembers = [
    [
        "name" => "Ryza Caryl Marcelo",
        "side" => "Buyer Part",
        "roles" => ["Project Leader", "Lead UI/UX Designer", "Frontend Developer"],
        "course" => "BSITWMA",
        "section" => "TW22",
        "email" => "rfmarcelo@fit.edu.ph",
        "image" => "images/Ryza.png",
        "socials" => ["facebook" => "#", "twitter" => "#", "instagram" => "#"]
    ],
    [
        "name" => "Geraldine Origenes",
        "side" => "Buyer Part",
        "roles" => ["Lead Frontend Developer", "Backend Developer"],
        "course" => "BSITWMA",
        "section" => "TW22",
        "email" => "ggorigenes@fit.edu.ph",
        "image" => "images/Geraldine.png",
        "socials" => ["facebook" => "#", "twitter" => "#", "instagram" => "#"]
    ],
    [
        "name" => "Clark Wayne Bagtas",
        "side" => "Seller Part",
        "roles" => ["Lead Backend Developer"],
        "course" => "BSITWMA",
        "section" => "TW22",
        "email" => "cdbagtas@fit.edu.ph",
        "image" => "images/Clark.png",
        "socials" => ["facebook" => "#", "twitter" => "#", "instagram" => "#"]
    ],
    [
        "name" => "Zyland Azriel Dacillo",
        "side" => "Seller Part",
        "roles" => ["UI/UX Designer", "Frontend Developer"],
        "course" => "BSITWMA",
        "section" => "TW22",
        "email" => "zbdacillo@fit.edu.ph",
        "image" => "images/Zyland.jpg",
        "socials" => ["facebook" => "#", "twitter" => "#", "instagram" => "#"]
    ]
];

// Key Brand Statistics
$brandStats = [
    ["number" => "2026", "label" => "Established"],
    ["number" => "100%", "label" => "Custom Fits"],
    ["number" => "10k+", "label" => "Happy Clients"],
    ["number" => "15+", "label" => "Collections"],
    ["number" => "99%", "label" => "Satisfaction"]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?= (int) filemtime(__DIR__ . '/css/style.css') ?>">
    <link rel="stylesheet" href="css/AboutUs.css">
</head>
<body>

    <?php 
    $path = ''; 
    include 'includes/header.php'; 
    ?>

    <div class="main-container container-fluid my-4 px-3 px-md-5">

        <section class="text-center mb-5">
            <span class="section-subtitle">About Param</span>
            <h2 class="fw-bold brand-accent mt-1 mb-3">Effortless, Comfortable, & Timeless Style</h2>
            
            <div class="row justify-content-center mb-4">
                <div class="col-lg-10">
                    <p class="text-muted lead mb-3" style="font-size: 1.05rem;">
                        At <strong>Param</strong>, we believe that everyday style should feel effortless, comfortable, and timeless. Born from a desire to simplify your daily wardrobe, Param brings together high-quality essentials designed for every member of the family—Women, Men, and Kids.
                    </p>
                    <p class="text-muted" style="font-size: 1rem;">
                        From versatile everyday knits to functional outerwear, each piece in our collection is crafted with thoughtful details, soft fabrics, and enduring quality. Whether you're dressing for a relaxed weekend or updating your everyday essentials, Param is here to provide elevated basics that seamlessly fit into your life.
                    </p>
                </div>
            </div>

            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <img src="images/AboutUsImage1.jpg" alt="Param Apparel Collection" class="story-img-main shadow-sm">
                </div>
                <div class="col-md-6">
                    <div class="row g-3">
                        <div class="col-12">
                            <img src="images/AboutUsImage2.jpg" alt="Param Style Essentials" class="story-img-sub shadow-sm">
                        </div>
                        <div class="col-12">
                            <img src="images/AboutUsImage3.jpg" alt="Param Quality Details" class="story-img-sub shadow-sm">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="my-5">
            <div class="row text-center g-3 justify-content-center">
                <?php foreach ($brandStats as $stat): ?>
                    <div class="col-6 col-md">
                        <div class="stat-square-box shadow-sm">
                            <div class="stat-number"><?php echo htmlspecialchars($stat['number']); ?></div>
                            <div class="stat-label"><?php echo htmlspecialchars($stat['label']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="my-5">
            <div class="row align-items-center g-4">
                <div class="col-md-6">
                    <img src="images/AboutUsImage4.jpg" alt="Param Apparel Quality" class="feature-img shadow-sm">
                </div>
                <div class="col-md-6 ps-md-4">
                    <span class="section-subtitle">Our Product Quality</span>
                    <h2 class="fw-bold brand-accent mb-3">Premium Quality in Every Detail</h2>
                    <p class="text-muted mb-4">
                        We believe everyday essentials should never compromise on style or structure. From raw fiber to finished seam, our collection is built around balance, longevity, and ease.
                    </p>
                    
                    <div class="d-flex align-items-start mb-3">
                        <div class="feature-icon-box me-3">
                            <i class="fa-solid fa-gem"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold brand-accent mb-1">Thoughtfully Sourced</h6>
                            <p class="text-muted small mb-0">High-grade cottons and technical knits crafted for superior feel and resistance to daily wear.</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start">
                        <div class="feature-icon-box me-3">
                            <i class="fa-solid fa-shirt"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold brand-accent mb-1">All-Day Versatility</h6>
                            <p class="text-muted small mb-0">Adaptive, breathable layers tailored to move naturally with you throughout your day.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="my-5 text-center">
            <h2 class="fw-bold brand-accent mb-1">Meet Our Team</h2>
            <span class="section-subtitle d-block mb-4">The Crew Crafting Your Style</span>

            <div class="row g-4 justify-content-center">
                <?php foreach ($teamMembers as $member): ?>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="team-member-box p-3 shadow-sm text-center">
                            
                            <div class="team-img-container mb-3">
                                <img src="<?php echo htmlspecialchars($member['image']); ?>" 
                                    alt="<?php echo htmlspecialchars($member['name']); ?>" 
                                    <?php echo urlencode($member['name']); ?>>
                                
                                <div class="social-overlay">
                                    <a href="<?php echo $member['socials']['facebook']; ?>"><i class="fa-brands fa-facebook-f"></i></a>
                                    <a href="<?php echo $member['socials']['twitter']; ?>"><i class="fa-brands fa-twitter"></i></a>
                                    <a href="<?php echo $member['socials']['instagram']; ?>"><i class="fa-brands fa-instagram"></i></a>
                                </div>
                            </div>
                            
                            <h5 class="fw-bold brand-accent mb-2 text-wrap" style="font-size: 1.1rem;"><?php echo htmlspecialchars($member['name']); ?></h5>
                            
                            <div class="d-flex flex-wrap justify-content-center gap-1 mb-3">
                                <span class="badge badge-red-side px-2 py-1"><?php echo htmlspecialchars($member['side']); ?></span>
                                
                                <?php foreach ($member['roles'] as $role): ?>
                                    <span class="badge badge-yellow-role px-2 py-1"><?php echo htmlspecialchars($role); ?></span>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="team-info-details pt-3 border-top text-start">
                                <p class="mb-2 small text-nowrap-item">
                                    <i class="fa-solid fa-graduation-cap me-1 text-gold"></i>
                                    <strong>Course:</strong> <span class="text-muted"><?php echo htmlspecialchars($member['course']); ?></span>
                                </p>
                                <p class="mb-2 small text-nowrap-item">
                                    <i class="fa-solid fa-users-rectangle me-1 text-gold"></i>
                                    <strong>Section:</strong> <span class="text-muted"><?php echo htmlspecialchars($member['section']); ?></span>
                                </p>
                                <p class="mb-0 small text-nowrap-item">
                                    <i class="fa-solid fa-envelope me-1 text-gold"></i>
                                    <strong>Email:</strong> <span class="text-muted text-break"><?php echo htmlspecialchars($member['email']); ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    </div>
    
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

<?php 
$path = ''; 
include 'includes/footer.php'; 
?>
