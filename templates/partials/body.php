<?php
/**
 * Dynamic body section — rendered once per snapshot frame.
 *
 * @var array $atr Context array (live or snapshot-derived)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$report     = $atr['report'];
$totals     = $atr['totals'];
$hero_stats = $atr['hero_stats'];
$config     = $atr['config'];
$ledes      = $atr['ledes'];
$timeline   = $atr['timeline'];
$platforms  = $atr['platforms'];
$comments   = $atr['comments'];
$press      = $atr['press'];
$referrers  = $atr['referrers'];
$countries  = $atr['countries'];
$browsers   = $atr['browsers'];
$devices    = $atr['devices'];
$chart      = $atr['chart'];

$kicker       = $report['kicker']        ?: '';
$headline     = $report['headline_html'] ?: '';
$total_visits = (int) ( $totals['visits'] ?? 0 );

$ref_max = 0;
foreach ( $referrers as $r ) $ref_max = max( $ref_max, (int) $r['visits'] );
if ( $ref_max <= 0 ) $ref_max = 1;
?>
  <!-- HERO -->
  <section class="hero">
    <?php if ( $kicker ) : ?><div class="kicker"><?php echo esc_html( $kicker ); ?></div><?php endif; ?>
    <?php if ( $headline ) : ?><h1 class="hero-title"><?php echo $headline; ?></h1><?php endif; ?>
    <?php if ( ! empty( $report['hero_subtitle_html'] ) ) : ?>
      <p class="hero-sub"><?php echo $report['hero_subtitle_html']; ?></p>
    <?php endif; ?>

    <?php if ( $hero_stats ) : ?>
      <div class="bigstats num">
        <?php foreach ( $hero_stats as $s ) : ?>
          <div class="bigstat">
            <div class="v"><?php echo esc_html( $s['value'] ?? '' ); ?></div>
            <div class="l"><?php echo esc_html( $s['label'] ?? '' ); ?></div>
            <?php if ( ! empty( $s['note'] ) ) : ?><div class="s"><?php echo esc_html( $s['note'] ); ?></div><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <?php if ( $timeline ) : ?>
  <!-- TIMELINE -->
  <section>
    <div class="eyebrow">Sequence of Events</div>
    <h2><?php echo esc_html( $ledes['timeline_title'] ?? 'How the spike actually unfolded' ); ?></h2>
    <?php if ( ! empty( $ledes['timeline'] ) ) : ?>
      <p class="lede"><?php echo wp_kses_post( $ledes['timeline'] ); ?></p>
    <?php endif; ?>

    <div class="timeline" style="grid-template-columns: repeat(<?php echo count( $timeline ); ?>, 1fr);">
      <?php foreach ( $timeline as $tl ) :
        $parts = atr_utc_to_est_parts( $tl['event_at'] );
        $cls = ( $tl['marker'] ?? 'info' ) === 'fire' ? 'tl-card fire' : 'tl-card';
      ?>
        <div class="<?php echo esc_attr( $cls ); ?>">
          <div class="tl-time"><span><?php echo esc_html( $parts['date'] ); ?></span><span class="utc"><?php echo esc_html( $parts['time'] ); ?> EST</span></div>
          <div class="tl-where"><?php echo esc_html( $tl['label'] ); ?></div>
          <?php if ( ! empty( $tl['description_html'] ) ) : ?>
            <div class="tl-note"><?php echo $tl['description_html']; ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ( $chart['count'] > 0 ) : ?>
    <!-- CHART -->
    <div class="chart-card">
      <div class="chart-head">
        <div class="t">Visits per hour · <?php echo esc_html( $chart['range_label'] ); ?></div>
        <div class="r num">peak <?php echo number_format( $chart['peak'] ); ?> · floor <?php echo number_format( $chart['floor'] ); ?> · <?php echo (int) $chart['count']; ?> hrs shown</div>
      </div>
      <svg viewBox="0 0 1040 220" width="100%" height="220" preserveAspectRatio="none" aria-label="Hourly traffic chart">
        <defs>
          <linearGradient id="bg1-<?php echo esc_attr( $atr['_frame_id'] ?? '0' ); ?>" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%"   stop-color="var(--chart-1)" stop-opacity=".95"/>
            <stop offset="100%" stop-color="var(--chart-1)" stop-opacity=".25"/>
          </linearGradient>
          <linearGradient id="bg2-<?php echo esc_attr( $atr['_frame_id'] ?? '0' ); ?>" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%"   stop-color="var(--chart-2)" stop-opacity=".85"/>
            <stop offset="100%" stop-color="var(--chart-2)" stop-opacity=".18"/>
          </linearGradient>
        </defs>
        <g stroke="var(--line-soft)" stroke-width="1">
          <line x1="0" y1="40"  x2="1040" y2="40"/>
          <line x1="0" y1="100" x2="1040" y2="100"/>
          <line x1="0" y1="160" x2="1040" y2="160"/>
        </g>
        <?php foreach ( $chart['markers'] as $m ) : ?>
          <g>
            <line x1="<?php echo (int) $m['x']; ?>" y1="14" x2="<?php echo (int) $m['x']; ?>" y2="180" stroke="var(--accent)" stroke-dasharray="3 4" stroke-width="1"/>
            <text x="<?php echo (int) ( $m['x'] - 4 ); ?>" y="10" text-anchor="end" fill="var(--accent-soft)" font-size="11" font-family="-apple-system, sans-serif"><?php echo esc_html( $m['label'] ); ?> ↓</text>
          </g>
        <?php endforeach; ?>
        <g>
          <?php foreach ( $chart['bars'] as $b ) : ?>
            <rect x="<?php echo (int) $b['x']; ?>" y="<?php echo (int) $b['y']; ?>" width="<?php echo (int) $b['w']; ?>" height="<?php echo (int) $b['h']; ?>" rx="2" fill="url(#<?php echo esc_attr( $b['color'] ); ?>-<?php echo esc_attr( $atr['_frame_id'] ?? '0' ); ?>)" <?php if ( $b['opacity'] !== '1' ) echo 'opacity="' . esc_attr( $b['opacity'] ) . '"'; ?>/>
          <?php endforeach; ?>
        </g>
        <g fill="var(--dim)" font-size="10" font-family="-apple-system, sans-serif">
          <text x="4" y="36">2,000</text>
          <text x="4" y="96">1,000</text>
          <text x="4" y="156">500</text>
        </g>
      </svg>
      <div class="num" style="display:grid;grid-template-columns:repeat(<?php echo (int) $chart['count']; ?>,1fr);gap:0;color:var(--dim);font-size:<?php echo $chart['count'] > 50 ? 8 : ( $chart['count'] > 30 ? 9 : 10 ); ?>px;letter-spacing:0;padding:6px 6px 4px;text-align:center;">
        <?php foreach ( $chart['labels'] as $lab ) : ?><div><?php echo esc_html( $lab ); ?></div><?php endforeach; ?>
      </div>
      <?php
      $day_cols = [];
      foreach ( $chart['days'] as $d ) $day_cols[] = (int) $d['span'] . 'fr';
      ?>
      <div style="display:grid;grid-template-columns:<?php echo esc_attr( implode( ' ', $day_cols ) ); ?>;color:var(--dim);font-size:11px;padding:0 6px 6px;">
        <?php foreach ( $chart['days'] as $i => $d ) :
          $align = $i === count( $chart['days'] ) - 1 ? 'right' : 'left';
          $extra = $i === 0 ? ' (EST)' : '';
        ?>
          <span style="text-align:<?php echo $align; ?>;"><?php echo esc_html( $d['label'] . $extra ); ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ( $platforms ) : ?>
  <!-- PLATFORMS -->
  <section>
    <div class="eyebrow">By Platform</div>
    <h2><?php echo esc_html( $ledes['platforms_title'] ?? 'Where the traffic actually came from' ); ?></h2>
    <?php if ( ! empty( $ledes['platforms'] ) ) : ?>
      <p class="lede"><?php echo wp_kses_post( $ledes['platforms'] ); ?></p>
    <?php endif; ?>

    <div class="platforms">
      <?php
      $top = array_slice( $platforms, 0, 2 );
      $rest = array_slice( $platforms, 2 );
      foreach ( $top as $p ) : ?>
        <a class="pcard <?php echo esc_attr( $p['accent'] ?: 'hn' ); ?>" href="<?php echo esc_url( $p['url'] ); ?>" target="_blank" rel="noopener">
          <span class="arrow" aria-hidden="true">↗</span>
          <div class="ph">
            <div class="src"><span class="badge <?php echo esc_attr( $p['accent'] ?: 'hn' ); ?>"><?php echo esc_html( $p['badge'] ?: '·' ); ?></span> <?php echo esc_html( $p['label'] ); ?></div>
            <?php if ( ! empty( $p['posted_label'] ) ) : ?><div class="when"><?php echo esc_html( $p['posted_label'] ); ?></div><?php endif; ?>
          </div>
          <?php if ( ! empty( $p['headline_html'] ) ) : ?><div class="head"><?php echo $p['headline_html']; ?></div><?php endif; ?>
          <?php $stats = $p['_stats'] ?? []; if ( $stats ) : ?>
          <div class="gridstats">
            <?php foreach ( $stats as $s ) : ?>
              <div class="pmini"><div class="v num"><?php echo esc_html( $s['value'] ?? '' ); ?></div><div class="l"><?php echo esc_html( $s['label'] ?? '' ); ?></div></div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <?php if ( ! empty( $p['meta_html'] ) ) : ?><div class="meta"><?php echo $p['meta_html']; ?></div><?php endif; ?>
        </a>
      <?php endforeach;

      if ( $rest ) : ?>
        <div class="row2" style="grid-template-columns: repeat(<?php echo count( $rest ); ?>, 1fr);">
          <?php foreach ( $rest as $p ) : ?>
            <a class="pcard <?php echo esc_attr( $p['accent'] ?: 'rd' ); ?>" href="<?php echo esc_url( $p['url'] ); ?>" target="_blank" rel="noopener">
              <span class="arrow" aria-hidden="true">↗</span>
              <div class="ph">
                <div class="src"><span class="badge <?php echo esc_attr( $p['accent'] ?: 'rd' ); ?>"><?php echo esc_html( $p['badge'] ?: '·' ); ?></span> <?php echo esc_html( $p['label'] ); ?></div>
                <?php if ( ! empty( $p['posted_label'] ) ) : ?><div class="when"><?php echo esc_html( $p['posted_label'] ); ?></div><?php endif; ?>
              </div>
              <?php if ( ! empty( $p['headline_html'] ) ) : ?><div class="head"><?php echo $p['headline_html']; ?></div><?php endif; ?>
              <?php $stats = $p['_stats'] ?? []; if ( $stats ) : ?>
              <div class="gridstats">
                <?php foreach ( $stats as $s ) : ?>
                  <div class="pmini"><div class="v num"><?php echo esc_html( $s['value'] ?? '' ); ?></div><div class="l"><?php echo esc_html( $s['label'] ?? '' ); ?></div></div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
              <?php if ( ! empty( $p['meta_html'] ) ) : ?><div class="meta"><?php echo $p['meta_html']; ?></div><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( $referrers ) : ?>
  <!-- REFERRERS TABLE -->
  <section>
    <div class="eyebrow">The Long Tail</div>
    <h2><?php echo esc_html( $ledes['referrers_title'] ?? 'Top referrers, including the aggregators' ); ?></h2>
    <?php if ( ! empty( $ledes['referrers'] ) ) : ?>
      <p class="lede"><?php echo wp_kses_post( $ledes['referrers'] ); ?></p>
    <?php endif; ?>

    <div class="reftable num">
      <div class="row head"><div></div><div>Referrer</div><div style="text-align:right">Visits</div><div></div></div>
      <?php foreach ( $referrers as $i => $r ) :
        $width = ( (int) $r['visits'] / $ref_max ) * 100; ?>
        <div class="row">
          <div class="rank"><?php echo str_pad( $i + 1, 2, '0', STR_PAD_LEFT ); ?></div>
          <div class="ref"><strong><?php echo esc_html( $r['label'] ?: $r['d_key'] ); ?></strong>
            <?php if ( ! empty( $r['note'] ) ) : ?><span style="color:var(--dim)">· <?php echo esc_html( $r['note'] ); ?></span><?php endif; ?>
          </div>
          <div class="visits"><?php echo number_format( (int) $r['visits'] ); ?></div>
          <div class="vbar"><span style="width:<?php echo number_format( $width, 2 ); ?>%"></span></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( $comments ) : ?>
  <!-- NOTABLE COMMENTS -->
  <section>
    <div class="eyebrow">In the Replies</div>
    <h2><?php echo esc_html( $ledes['comments_title'] ?? 'Notable comments along the way' ); ?></h2>
    <?php if ( ! empty( $ledes['comments'] ) ) : ?>
      <p class="lede"><?php echo wp_kses_post( $ledes['comments'] ); ?></p>
    <?php endif; ?>

    <div class="quotes">
      <?php
      $featured = array_values( array_filter( $comments, fn( $c ) => ! empty( $c['featured'] ) ) );
      $regular  = array_values( array_filter( $comments, fn( $c ) => empty( $c['featured'] ) ) );

      $top1 = $featured[0] ?? ( $regular[0] ?? null );
      $top2 = $featured[1] ?? ( $regular[ $featured ? 0 : 1 ] ?? null );
      $used_ids = array_filter( [ $top1['id'] ?? null, $top2['id'] ?? null ] );
      $rest_q = array_values( array_filter( array_merge( $featured, $regular ), fn( $c ) => ! in_array( $c['id'], $used_ids ) ) );

      foreach ( [ $top1, $top2 ] as $c ) :
        if ( ! $c ) continue;
        $is_feat = ! empty( $c['featured'] );
        $cls = 'qcard ' . ( $is_feat ? 'featured' : strtolower( $c['source_kind'] ) );
      ?>
        <a class="<?php echo esc_attr( $cls ); ?>" href="<?php echo esc_url( $c['url'] ); ?>" target="_blank" rel="noopener">
          <div class="qhead">
            <div class="av"<?php if ( ! empty( $c['avatar_style'] ) ) echo ' style="' . esc_attr( $c['avatar_style'] ) . '"'; ?>><?php echo esc_html( $c['avatar'] ?: substr( $c['author'], 0, 1 ) ); ?></div>
            <div class="who">
              <div class="name"><?php echo esc_html( $c['author'] ); ?> <?php if ( ! empty( $c['handle_html'] ) ) : ?><span style="color:var(--muted);font-weight:500;"><?php echo $c['handle_html']; ?></span><?php endif; ?></div>
              <?php if ( ! empty( $c['source_label'] ) ) : ?><div class="handle"><?php echo esc_html( $c['source_label'] ); ?></div><?php endif; ?>
            </div>
            <?php if ( ! empty( $c['role_label'] ) ) : ?><div class="role"><?php echo esc_html( $c['role_label'] ); ?></div><?php endif; ?>
          </div>
          <blockquote><?php echo $c['body_html']; ?></blockquote>
          <div class="qmeta">
            <span class="src"><?php echo esc_html( atr_format_quote_meta( $c ) ); ?></span>
            <span class="arrow">↗</span>
          </div>
        </a>
      <?php endforeach; ?>

      <?php if ( $rest_q ) : ?>
        <div class="row2" style="grid-template-columns: repeat(<?php echo count( $rest_q ); ?>, 1fr);">
          <?php foreach ( $rest_q as $c ) :
            $cls = 'qcard ' . strtolower( $c['source_kind'] );
          ?>
            <a class="<?php echo esc_attr( $cls ); ?>" href="<?php echo esc_url( $c['url'] ); ?>" target="_blank" rel="noopener">
              <div class="qhead">
                <div class="av"><?php echo esc_html( $c['avatar'] ?: substr( $c['author'], 0, 1 ) ); ?></div>
                <div class="who">
                  <div class="name"><?php echo esc_html( $c['author'] ); ?></div>
                  <?php if ( ! empty( $c['source_label'] ) ) : ?><div class="handle"><?php echo esc_html( $c['source_label'] ); ?></div><?php endif; ?>
                </div>
                <?php if ( ! empty( $c['role_label'] ) ) : ?><div class="role"><?php echo esc_html( $c['role_label'] ); ?></div><?php endif; ?>
              </div>
              <blockquote><?php echo $c['body_html']; ?></blockquote>
              <div class="qmeta">
                <span class="src"><?php echo esc_html( atr_format_quote_meta( $c ) ); ?></span>
                <span class="arrow">↗</span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ( $countries || $devices || $browsers ) : ?>
  <!-- AUDIENCE -->
  <section>
    <div class="eyebrow">Audience</div>
    <h2><?php echo esc_html( $ledes['audience_title'] ?? 'Who actually showed up' ); ?></h2>
    <?php if ( ! empty( $ledes['audience'] ) ) : ?>
      <p class="lede"><?php echo wp_kses_post( $ledes['audience'] ); ?></p>
    <?php endif; ?>

    <div class="audience">
      <?php if ( $countries ) :
        $top_countries = array_slice( $countries, 0, 10 ); ?>
        <div class="acard">
          <h3>Top countries</h3>
          <ul class="geo num" style="list-style:none;margin:0;padding:0;">
            <?php foreach ( $top_countries as $c ) :
              $pct = $total_visits > 0 ? ( (int) $c['visits'] / $total_visits ) * 100 : 0;
              $flag = ! empty( $c['meta'] ) ? ( atr_json_decode( $c['meta'] )['flag'] ?? '' ) : ''; ?>
              <li>
                <span class="flag"><?php echo $flag ? esc_html( $flag ) : ''; ?></span>
                <span class="name"><?php echo esc_html( $c['label'] ?: $c['d_key'] ); ?></span>
                <span class="n"><?php echo number_format( (int) $c['visits'] ); ?></span>
                <span class="pct"><?php echo number_format( $pct, 1 ); ?>%</span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ( $devices || $browsers ) :
        $dev_total = array_sum( array_column( $devices, 'visits' ) ) ?: 1;
        $br_total  = array_sum( array_column( $browsers, 'visits' ) ) ?: 1; ?>
        <div class="acard">
          <h3>Devices &amp; browsers</h3>
          <div class="twocol">
            <ul class="stack num">
              <?php foreach ( array_slice( $devices, 0, 4 ) as $d ) :
                $pct = round( (int) $d['visits'] / $dev_total * 100 );
                $icon = ! empty( $d['meta'] ) ? ( atr_json_decode( $d['meta'] )['icon'] ?? '' ) : '';
              ?>
                <li><span class="lbl"><?php echo $icon ? esc_html( $icon . ' ' ) : ''; ?><?php echo esc_html( $d['label'] ?: $d['d_key'] ); ?></span><span class="val"><?php echo (int) $pct; ?>%</span><span class="meter"><span style="width:<?php echo (int) $pct; ?>%"></span></span></li>
              <?php endforeach; ?>
            </ul>
            <ul class="stack num">
              <?php foreach ( array_slice( $browsers, 0, 5 ) as $b ) :
                $pct = round( (int) $b['visits'] / $br_total * 100 );
              ?>
                <li><span class="lbl"><?php echo esc_html( $b['label'] ?: $b['d_key'] ); ?></span><span class="val"><?php echo (int) $pct; ?>%</span><span class="meter"><span style="width:<?php echo (int) $pct; ?>%"></span></span></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <?php if ( ! empty( $report['context_callout_html'] ) ) : ?>
    <div class="callout">
      <div class="pip">↑</div>
      <p><?php echo $report['context_callout_html']; ?></p>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>
