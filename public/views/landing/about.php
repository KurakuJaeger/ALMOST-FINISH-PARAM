<?php
$brandStats = [
    ['number' => '2026', 'label' => 'Established'],
    ['number' => '100%', 'label' => 'Custom Fits'],
    ['number' => '10k+', 'label' => 'Happy Clients'],
    ['number' => '15+', 'label' => 'Collections'],
    ['number' => '99%', 'label' => 'Satisfaction'],
];

$teamMembers = [
    ['name' => 'Ryza Caryl Marcelo', 'side' => 'Buyer Part', 'roles' => ['Project Leader', 'Lead UI/UX Designer', 'Frontend Developer'], 'course' => 'BSITWMA', 'section' => 'TW22', 'email' => 'rfmarcelo@fit.edu.ph', 'image' => 'store/images/Ryza.png'],
    ['name' => 'Geraldine Origenes', 'side' => 'Buyer Part', 'roles' => ['Lead Frontend Developer', 'Backend Developer'], 'course' => 'BSITWMA', 'section' => 'TW22', 'email' => 'ggorigenes@fit.edu.ph', 'image' => 'store/images/Geraldine.png'],
    ['name' => 'Clark Wayne Bagtas', 'side' => 'Seller Part', 'roles' => ['Lead Backend Developer'], 'course' => 'BSITWMA', 'section' => 'TW22', 'email' => 'cdbagtas@fit.edu.ph', 'image' => 'store/images/Clark.png'],
    ['name' => 'Zyland Azriel Dacillo', 'side' => 'Seller Part', 'roles' => ['UI/UX Designer', 'Frontend Developer'], 'course' => 'BSITWMA', 'section' => 'TW22', 'email' => 'zbdacillo@fit.edu.ph', 'image' => 'store/images/Zyland.jpg'],
];
?>
<section class="section peer-about" id="about">
    <div class="container">
        <div class="peer-story-heading reveal">
            <span class="section-subtitle">About PARAM</span>
            <h2>Effortless, Comfortable, &amp; Timeless Style</h2>
            <p>At <strong>PARAM</strong>, we believe that everyday style should feel effortless, comfortable, and timeless. Born from a desire to simplify your daily wardrobe, PARAM brings together high-quality essentials designed for every member of the family—Women, Men, and Kids.</p>
            <p>From versatile everyday knits to functional outerwear, each piece in our collection is crafted with thoughtful details, soft fabrics, and enduring quality. Whether you're dressing for a relaxed weekend or updating your everyday essentials, PARAM is here to provide elevated basics that seamlessly fit into your life.</p>
        </div>

        <div class="peer-story-gallery reveal">
            <img class="peer-story-main" src="<?= htmlspecialchars(appUrl('store/images/AboutUsImage1.jpg')) ?>" alt="PARAM apparel collection">
            <div class="peer-story-stack">
                <img src="<?= htmlspecialchars(appUrl('store/images/AboutUsImage2.jpg')) ?>" alt="PARAM style essentials">
                <img src="<?= htmlspecialchars(appUrl('store/images/AboutUsImage3.jpg')) ?>" alt="PARAM quality details">
            </div>
        </div>

        <div class="brand-stats reveal" aria-label="PARAM brand statistics">
            <?php foreach ($brandStats as $stat): ?>
                <div class="brand-stat"><strong><?= htmlspecialchars($stat['number']) ?></strong><span><?= htmlspecialchars($stat['label']) ?></span></div>
            <?php endforeach; ?>
        </div>

        <div class="quality-grid reveal">
            <img src="<?= htmlspecialchars(appUrl('store/images/AboutUsImage4.jpg')) ?>" alt="PARAM apparel quality">
            <div class="quality-copy">
                <span class="section-subtitle">Our Product Quality</span>
                <h2>Premium Quality in Every Detail</h2>
                <p>We believe everyday essentials should never compromise on style or structure. From raw fiber to finished seam, our collection is built around balance, longevity, and ease.</p>
                <div class="quality-point"><span class="quality-icon" aria-hidden="true">◆</span><div><strong>Thoughtfully Sourced</strong><p>High-grade cottons and technical knits crafted for superior feel and resistance to daily wear.</p></div></div>
                <div class="quality-point"><span class="quality-icon" aria-hidden="true">♟</span><div><strong>All-Day Versatility</strong><p>Adaptive, breathable layers tailored to move naturally with you throughout your day.</p></div></div>
            </div>
        </div>

        <div class="team-heading reveal" id="team">
            <h2>Meet Our Team</h2>
            <span class="section-subtitle">The Crew Crafting Your Style</span>
        </div>

        <div class="team-grid">
            <?php foreach ($teamMembers as $member): ?>
                <article class="team-card reveal">
                    <img src="<?= htmlspecialchars(appUrl($member['image'])) ?>" alt="<?= htmlspecialchars($member['name']) ?>">
                    <h3><?= htmlspecialchars($member['name']) ?></h3>
                    <div class="team-badges">
                        <span class="team-side"><?= htmlspecialchars($member['side']) ?></span>
                        <?php foreach ($member['roles'] as $role): ?><span class="team-role"><?= htmlspecialchars($role) ?></span><?php endforeach; ?>
                    </div>
                    <dl class="team-details">
                        <div><dt>Course:</dt><dd><?= htmlspecialchars($member['course']) ?></dd></div>
                        <div><dt>Section:</dt><dd><?= htmlspecialchars($member['section']) ?></dd></div>
                        <div><dt>Email:</dt><dd><a href="mailto:<?= htmlspecialchars($member['email']) ?>"><?= htmlspecialchars($member['email']) ?></a></dd></div>
                    </dl>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
