<?php
$services = [
    ['number' => '01', 'title' => 'Everyday Collections', 'description' => 'Easy-to-style essentials designed for workdays, weekends, and everything in between.'],
    ['number' => '02', 'title' => 'Comfortable Fits', 'description' => 'Thoughtful shapes and soft fabrics that give you freedom to move through your day.'],
    ['number' => '03', 'title' => 'Simple Shopping', 'description' => 'A clear online experience that makes it easy to discover, save, and shop your favorites.'],
];
?>
<section class="section section-soft" id="services">
    <div class="container">
        <div class="section-heading center reveal">
            <span class="eyebrow">What we offer</span>
            <h2>A wardrobe built around you.</h2>
            <p>Clothing that works harder, feels better, and stays relevant beyond a single season.</p>
        </div>

        <div class="card-grid">
            <?php foreach ($services as $service): ?>
                <article class="service-card reveal">
                    <div class="card-number"><?= htmlspecialchars($service['number']) ?></div>
                    <h3><?= htmlspecialchars($service['title']) ?></h3>
                    <p><?= htmlspecialchars($service['description']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
