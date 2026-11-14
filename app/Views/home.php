<?php
$openCount  = count($vacancies);
$isLoggedIn = !empty($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>JobTrack — Internal Careers Portal</title>
<meta name="description" content="Browse internal vacancies, apply in minutes and track every stage of your application.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&family=Instrument+Sans:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="assets/landing.css">
</head>
<body>

<!-- ============ Navbar ============ -->
<header class="navbar">
  <div class="container">
    <a class="brand" href="#home"><span class="brand-mark">J</span>Job<span>Track</span></a>

    <nav class="nav-links" aria-label="Main">
      <a href="#home">Home</a>
      <a href="#vacancies">Vacancies</a>
      <a href="#how-it-works">How It Works</a>
    </nav>

    <div class="nav-actions">
      <?php if ($isLoggedIn): ?>
        <span class="nav-greeting">Hi, <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span>
        <a class="btn btn-sm" href="index.php?route=applicant.dashboard">Dashboard</a>
        <a class="btn btn-sm" href="index.php?route=applications.mine">My Applications</a>
        <a class="btn btn-sm btn-solid" href="index.php?route=logout">Log out</a>
      <?php else: ?>
        <a class="btn btn-sm btn-solid" href="index.php?route=login">Employee log in</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main>

<!-- ============ Hero ============ -->
<section class="hero on-dark" id="home">
  <div class="container hero-grid">
    <div class="hero-copy">
      <div class="eyebrow"><span class="dot"></span>Internal Careers Portal</div>
      <h1>Your next role is <span class="highlight">already here.</span></h1>
      <p class="hero-lead">
        Browse open internal vacancies, apply in minutes, and follow every stage of your
        application in real time &mdash; no more chasing emails or guessing where things stand.
      </p>

      <div class="hero-actions">
        <?php if ($isLoggedIn): ?>
          <a class="btn btn-lg btn-solid" href="#vacancies">View open vacancies &rarr;</a>
          <a class="btn btn-lg" href="index.php?route=applications.mine">Track my applications</a>
        <?php else: ?>
          <a class="btn btn-lg btn-solid" href="index.php?route=login">Employee log in &rarr;</a>
        <?php endif; ?>
      </div>

      <ul class="hero-points">
        <li>For employees only</li>
        <li>One-click CV upload</li>
        <li>Live status history</li>
      </ul>
    </div>

    <div class="hero-visual" aria-hidden="true">
      <div class="tracker-card">
        <div class="tracker-head">
          <div class="tracker-avatar">SA</div>
          <div>
            <div class="tracker-role">Senior Analyst</div>
            <div class="tracker-dept">Finance Department</div>
          </div>
          <span class="tracker-tag">Example</span>
        </div>

        <ul class="stage-list">
          <li class="stage done"><span class="stage-dot">✓</span>Applied<span class="stage-when">Mon</span></li>
          <li class="stage done"><span class="stage-dot">✓</span>Screening<span class="stage-when">Wed</span></li>
          <li class="stage current"><span class="stage-dot"></span>Interview scheduled<span class="stage-when">Today</span></li>
          <li class="stage"><span class="stage-dot"></span>Interview completed</li>
          <li class="stage"><span class="stage-dot"></span>Decision</li>
        </ul>
      </div>

      <div class="float-badge">
        <span class="icon">🔔</span>
        <div>
          <strong>Status updated</strong>
          <small>Screening &rarr; Interview</small>
        </div>
      </div>
    </div>
  </div>

  <svg class="hero-wave" viewBox="0 0 1440 80" preserveAspectRatio="none" aria-hidden="true">
    <path fill="currentColor" d="M0,48 C240,88 480,88 720,56 C960,24 1200,16 1440,40 L1440,80 L0,80 Z"></path>
  </svg>
</section>

<!-- ============ Stats ============ -->
<section class="stats-strip">
  <div class="container">
    <div class="stats-card">
      <div class="stat"><div class="num"><?= (int) $openCount ?></div><div class="lbl">Open vacancies</div></div>
      <div class="stat"><div class="num">5</div><div class="lbl">Tracked stages</div></div>
      <div class="stat"><div class="num">100%</div><div class="lbl">Transparent process</div></div>
    </div>
  </div>
</section>

<!-- ============ Vacancies ============ -->
<section class="section" id="vacancies">
  <div class="container">
    <div class="section-head">
      <span class="section-kicker">Opportunities</span>
      <h2 class="section-title">Open vacancies</h2>
      <p class="section-sub"><?= $isLoggedIn ? 'Open positions in the organisation. Apply with your CV in a few clicks.' : 'Open positions are shown to signed-in employees only.' ?></p>
    </div>

    <?php if (!$isLoggedIn): ?>
      <div class="empty-state">
        <div class="icon">&#128274;</div>
        <strong>Log in to see open vacancies</strong>
        This is an internal portal. Use the account that HR or your administrator created for you.
        <p style="margin-top:16px;"><a class="btn btn-solid" href="index.php?route=login">Employee log in</a></p>
      </div>
    <?php elseif (empty($vacancies)): ?>
      <div class="empty-state">
        <div class="icon">📭</div>
        <strong>No open vacancies right now</strong>
        Please check back soon &mdash; new roles are posted regularly.
      </div>
    <?php else: ?>
      <div class="vac-grid">
        <?php foreach ($vacancies as $v):
            $id        = (int) $v['id'];
            $applyUrl  = 'index.php?route=applications.apply&vacancy_id=' . $id;
            $posted    = !empty($v['created_at']) ? date('j M Y', strtotime($v['created_at'])) : '';

            // Show only the first 120 characters of the description on the card.
            $shortDescription = $v['description'];
            if (strlen($shortDescription) > 120) {
                $shortDescription = substr($shortDescription, 0, 120) . '...';
            }
        ?>
          <article class="vac-card">
            <div class="vac-media">
              <?php if (!empty($v['image_path'])): ?>
                <img src="assets/uploads/vacancies/<?= htmlspecialchars($v['image_path']) ?>" alt="" loading="lazy">
              <?php else: ?>
                <span class="initial"><?= htmlspecialchars(mb_strtoupper(mb_substr($v['title'], 0, 1))) ?></span>
              <?php endif; ?>
            </div>
            <div class="vac-body">
              <span class="vac-dept"><?= htmlspecialchars($v['department']) ?></span>
              <h3 class="vac-title"><?= htmlspecialchars($v['title']) ?></h3>
              <p class="vac-desc"><?= htmlspecialchars($shortDescription) ?></p>
              <div class="vac-foot">
                <span class="vac-date"><?= $posted ? 'Posted ' . htmlspecialchars($posted) : '' ?></span>
                <a class="btn btn-sm btn-solid" href="<?= htmlspecialchars($applyUrl) ?>">Apply now</a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ============ How it works ============ -->
<section class="section alt" id="how-it-works">
  <div class="container">
    <div class="section-head">
      <span class="section-kicker">How it works</span>
      <h2 class="section-title">A clear, auditable pipeline</h2>
      <p class="section-sub">Not an email thread or a spreadsheet &mdash; every step is recorded.</p>
    </div>

    <div class="steps">
      <div class="step">
        <div class="step-num">1</div>
        <h3>Apply in minutes</h3>
        <p>Pick a role, attach your CV and submit. One application per vacancy, tracked from the moment you apply.</p>
      </div>
      <div class="step">
        <div class="step-num">2</div>
        <h3>Controlled review</h3>
        <p>Applications move through a fixed sequence &mdash; Applied, Screening, Interview, Decision. No step is ever skipped.</p>
      </div>
      <div class="step">
        <div class="step-num">3</div>
        <h3>Full transparency</h3>
        <p>See your exact stage and complete history at any time, and withdraw freely before a final decision.</p>
      </div>
    </div>
  </div>
</section>

<!-- ============ CTA ============ -->
<?php if (!$isLoggedIn): ?>
<section class="cta-band">
  <div class="container">
    <div class="cta-inner on-dark">
      <div>
        <h2>Already have an account?</h2>
        <p>Log in with the account HR created for you to see open roles and apply.</p>
      </div>
      <a class="btn btn-lg btn-solid" href="index.php?route=login">Employee log in</a>
    </div>
  </div>
</section>
<?php endif; ?>

</main>

<!-- ============ Footer ============ -->
<footer class="site-footer">
  <div class="container">
    <span class="brand"><span class="brand-mark">J</span>JobTrack</span>
    <span>Internal Job Application Tracking System &mdash; MIT4301 Independent Study</span>
  </div>
</footer>

</body>
</html>
