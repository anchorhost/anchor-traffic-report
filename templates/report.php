<?php
/**
 * Editorial-style traffic report template (multi-snapshot).
 *
 * @var array $atr_report Top-level report row
 * @var array $atr_frames Array of frames: [{id, label, captured_at, ctx, is_live, pct}]
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$report      = $atr_report;
$frames      = $atr_frames;
$config      = atr_json_decode( $report['config'] );
$brand       = $config['brand']      ?? 'Anchor Hosting · Traffic Report';
$date_label  = $config['date_label'] ?? '';
$multi       = count( $frames ) > 1;
$default_idx = count( $frames ) - 1; // latest visible by default

// Tag each frame's context with its frame id for unique gradient ids.
foreach ( $frames as $i => &$f ) {
    $f['ctx']['_frame_id'] = $f['id'];
}
unset( $f );
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $report['title'] ); ?></title>
<script>
  (function () {
    try {
      var stored = localStorage.getItem('report-theme');
      var prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
      var theme = stored || (prefersLight ? 'light' : 'dark');
      if (theme === 'light') document.documentElement.setAttribute('data-theme', 'light');
    } catch (e) {}
  })();
  function toggleTheme () {
    var root = document.documentElement;
    var next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    if (next === 'light') root.setAttribute('data-theme', 'light');
    else root.removeAttribute('data-theme');
    try { localStorage.setItem('report-theme', next); } catch (e) {}
  }
</script>
<style>
<?php include ATR_PATH . 'templates/partials/styles.css.php'; ?>
</style>
</head>
<body>
<div class="wrap">

  <header class="masthead">
    <div class="brand"><span class="dot"></span> <?php echo esc_html( $brand ); ?></div>
    <div class="right">
      <?php if ( $multi ) : ?>
        <span id="atr-current" class="masthead-current"><?php echo esc_html( $frames[ $default_idx ]['label'] ); ?></span>
      <?php elseif ( $date_label ) : ?>
        <span><?php echo esc_html( $date_label ); ?></span>
      <?php endif; ?>
      <button class="theme-toggle" type="button" aria-label="Toggle color theme" onclick="toggleTheme()">
        <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
        </svg>
        <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="12" cy="12" r="4"/>
          <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
        </svg>
      </button>
    </div>
  </header>

  <?php if ( ! empty( $report['post_url'] ) ) :
    $purl = $report['post_url'];
    $parts = wp_parse_url( $purl );
    $host  = $parts['host'] ?? '';
    $path  = ( $parts['path'] ?? '' );
    if ( $parts['query']    ?? '' ) $path .= '?' . $parts['query'];
    if ( $parts['fragment'] ?? '' ) $path .= '#' . $parts['fragment'];
  ?>
  <a class="post-url" href="<?php echo esc_url( $purl ); ?>" target="_blank" rel="noopener">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
      <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
    </svg>
    <span class="host"><?php echo esc_html( $host ); ?></span><span class="path"><?php echo esc_html( $path ); ?></span>
    <span class="ext" aria-hidden="true">↗</span>
  </a>
  <?php endif; ?>

  <?php if ( $multi ) : ?>
  <nav class="scrubber" aria-label="Snapshot timeline">
    <div class="scrub-track">
      <div class="scrub-rail"></div>
      <?php foreach ( $frames as $i => $f ) :
        $est = atr_utc_to_est_parts( $f['captured_at'] );
        $is_active = $i === $default_idx; ?>
        <button type="button"
                class="scrub-dot <?php echo $is_active ? 'active' : ''; ?> <?php echo $f['is_live'] ? 'live' : ''; ?>"
                style="left: <?php echo number_format( $f['pct'], 2 ); ?>%"
                data-snap-id="<?php echo esc_attr( $f['id'] ); ?>"
                data-label="<?php echo esc_attr( $f['label'] ); ?>"
                aria-label="<?php echo esc_attr( $f['label'] . ' — ' . $est['date'] . ' ' . $est['time'] . ' EST' ); ?>">
          <span class="scrub-tip">
            <strong><?php echo esc_html( $f['label'] ); ?></strong>
            <span><?php echo esc_html( $est['date'] . ' · ' . $est['time'] . ' EST' ); ?></span>
          </span>
        </button>
      <?php endforeach; ?>
    </div>
  </nav>
  <?php endif; ?>

  <?php foreach ( $frames as $i => $f ) :
    $hidden = $i !== $default_idx ? 'hidden' : '';
    $atr = $f['ctx'];
  ?>
    <div class="snap-body" data-snap="<?php echo esc_attr( $f['id'] ); ?>" <?php echo $hidden; ?>>
      <?php include ATR_PATH . 'templates/partials/body.php'; ?>
    </div>
  <?php endforeach; ?>

  <footer class="report-foot">
    <div><?php echo esc_html( $config['sources_label'] ?? 'SOURCES · Fathom Analytics · Hacker News API · Reddit JSON · X / Twitter' ); ?></div>
    <?php if ( ! empty( $report['refreshed_at'] ) ) :
      $refreshed_est = atr_utc_to_est_parts( $report['refreshed_at'] );
    ?>
      <div>Last refreshed <?php echo esc_html( substr( $report['refreshed_at'], 0, 10 ) ); ?> · <?php echo esc_html( $refreshed_est['time'] ); ?> EST</div>
    <?php endif; ?>
  </footer>
</div>

<?php if ( $multi ) : ?>
<script>
(function () {
  var dots = document.querySelectorAll('.scrub-dot');
  var bodies = document.querySelectorAll('.snap-body');
  var current = document.getElementById('atr-current');
  function activate(id, label) {
    bodies.forEach(function (b) {
      if (b.dataset.snap === id) b.removeAttribute('hidden');
      else b.setAttribute('hidden', '');
    });
    dots.forEach(function (d) {
      d.classList.toggle('active', d.dataset.snapId === id);
    });
    if (current && label) current.textContent = label;
  }
  dots.forEach(function (d) {
    d.addEventListener('click', function () { activate(d.dataset.snapId, d.dataset.label); });
    d.addEventListener('keydown', function (e) {
      var idx = Array.prototype.indexOf.call(dots, d);
      if (e.key === 'ArrowLeft' && idx > 0) {
        e.preventDefault(); dots[idx - 1].focus(); dots[idx - 1].click();
      } else if (e.key === 'ArrowRight' && idx < dots.length - 1) {
        e.preventDefault(); dots[idx + 1].focus(); dots[idx + 1].click();
      }
    });
  });
})();
</script>
<?php endif; ?>
</body>
</html>
