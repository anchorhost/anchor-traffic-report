<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
  :root {
    --bg: #0a0e1a;
    --bg-2: #11172a;
    --card: #141a2e;
    --card-2: #1a2138;
    --line: #232b48;
    --line-soft: #1c2238;
    --text: #e8ecf5;
    --muted: #8a93b0;
    --dim: #5b647f;
    --accent: #ff5b3a;
    --accent-soft: #ff8e6e;
    --teal: #4ade80;
    --sky: #5fb4ff;
    --gold: #f5c062;
    --shadow: 0 1px 0 rgba(255,255,255,.04) inset, 0 30px 60px -30px rgba(0,0,0,.5);
    --hn-tint: rgba(255,102,0,.10);
    --x-tint:  rgba(95,180,255,.10);
    --rd-tint: rgba(255,69,0,.08);
    --chart-1: #ff5b3a;
    --chart-2: #5fb4ff;
    --hl: rgba(255,91,58,.22);
    color-scheme: dark;
  }
  [data-theme="light"] {
    --bg: #f7f5ee;
    --bg-2: #efece1;
    --card: #ffffff;
    --card-2: #faf8f1;
    --line: #d6d1be;
    --line-soft: #e7e2d1;
    --text: #161a26;
    --muted: #5a6275;
    --dim: #8a93a8;
    --accent: #d94220;
    --accent-soft: #ff6a3c;
    --teal: #15a04b;
    --sky: #2867d9;
    --gold: #b07a1c;
    --shadow: 0 1px 0 rgba(0,0,0,.02) inset, 0 22px 50px -28px rgba(20,30,60,.18);
    --hn-tint: rgba(255,102,0,.08);
    --x-tint:  rgba(40,103,217,.08);
    --rd-tint: rgba(255,69,0,.06);
    --chart-1: #d94220;
    --chart-2: #2867d9;
    --hl: rgba(217,66,32,.22);
    color-scheme: light;
  }
  * { box-sizing: border-box; }
  html, body { background: var(--bg); color: var(--text); }
  body {
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Inter", "Segoe UI", system-ui, sans-serif;
    font-size: 16px; line-height: 1.6;
    -webkit-font-smoothing: antialiased;
    font-feature-settings: "ss01", "cv11";
  }
  .wrap { max-width: 1080px; margin: 0 auto; padding: 56px 28px 96px; }
  a { color: inherit; text-decoration: none; }
  a.link { color: var(--accent-soft); border-bottom: 1px solid rgba(255,142,110,.25); }
  a.link:hover { color: var(--accent); border-bottom-color: var(--accent); }
  .num { font-variant-numeric: tabular-nums; font-feature-settings: "tnum"; }
  .mono { font-family: "SF Mono", "JetBrains Mono", ui-monospace, Menlo, monospace; letter-spacing: .01em; }

  /* ---------- post URL pill ---------- */
  .post-url {
    display: inline-flex; align-items: center; gap: 8px;
    margin-top: 22px;
    padding: 7px 14px 7px 12px;
    border: 1px solid var(--line-soft);
    border-radius: 99px;
    background: var(--card);
    color: var(--muted);
    font-family: "SF Mono", "JetBrains Mono", ui-monospace, Menlo, monospace;
    font-size: 12px; letter-spacing: .01em; line-height: 1;
    text-decoration: none;
    max-width: 100%;
    transition: color .15s ease, border-color .15s ease, background .15s ease, transform .15s ease;
  }
  .post-url:hover { color: var(--text); border-color: var(--line); transform: translateY(-1px); }
  .post-url svg { width: 14px; height: 14px; flex: 0 0 14px; color: var(--accent); }
  .post-url .host { color: var(--dim); }
  .post-url .path { color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }
  .post-url:hover .path { color: var(--accent-soft); }
  .post-url .ext { color: var(--dim); margin-left: 2px; font-family: -apple-system, BlinkMacSystemFont, sans-serif; }
  .post-url:hover .ext { color: var(--text); }

  /* ---------- snapshot scrubber ---------- */
  .scrubber {
    margin-top: 24px;
    padding: 20px 24px;
    border: 1px solid var(--line-soft);
    border-radius: 14px;
    background: linear-gradient(180deg, var(--card), var(--bg-2));
    box-shadow: var(--shadow);
  }
  .scrub-track { position: relative; height: 16px; padding: 0 8px; }
  .masthead-current { color: var(--accent); font-weight: 700; }
  .scrub-rail {
    position: absolute; left: 8px; right: 8px; top: 50%;
    height: 2px; transform: translateY(-50%);
    background: linear-gradient(90deg, var(--line-soft), var(--line), var(--line-soft));
    border-radius: 99px;
  }
  .scrub-dot {
    position: absolute; top: 50%; transform: translate(-50%, -50%);
    width: 14px; height: 14px; border-radius: 99px;
    background: var(--card); border: 2px solid var(--line);
    cursor: pointer; padding: 0;
    transition: transform .15s ease, background .15s ease, border-color .15s ease;
    z-index: 1;
  }
  .scrub-dot:hover { transform: translate(-50%, -50%) scale(1.25); border-color: var(--accent-soft); z-index: 3; }
  .scrub-dot:focus { outline: 2px solid var(--accent); outline-offset: 2px; }
  .scrub-dot.active { background: var(--accent); border-color: var(--accent); box-shadow: 0 0 0 5px rgba(255,91,58,.18); z-index: 2; }
  .scrub-dot.live::after {
    content: ''; position: absolute; left: 50%; top: 50%;
    width: 28px; height: 28px; border-radius: 99px;
    transform: translate(-50%, -50%);
    border: 1.5px dashed var(--accent-soft); opacity: .55;
  }
  .scrub-tip {
    position: absolute; bottom: calc(100% + 10px); left: 50%; transform: translateX(-50%);
    background: var(--card); border: 1px solid var(--line-soft); border-radius: 8px;
    padding: 6px 10px; font-size: 11px; line-height: 1.35; white-space: nowrap;
    color: var(--text); pointer-events: none; opacity: 0; transition: opacity .12s ease;
    box-shadow: 0 8px 20px -8px rgba(0,0,0,.4);
  }
  .scrub-tip strong { display: block; font-weight: 700; font-size: 12px; }
  .scrub-tip span { color: var(--muted); }
  .scrub-dot:hover .scrub-tip,
  .scrub-dot:focus .scrub-tip { opacity: 1; }

  .snap-body[hidden] { display: none; }

  .masthead {
    display: flex; align-items: center; justify-content: space-between;
    padding-bottom: 28px; border-bottom: 1px solid var(--line-soft);
    color: var(--muted); font-size: 13px; letter-spacing: .14em; text-transform: uppercase;
  }
  .masthead .brand { display: flex; align-items: center; gap: 10px; color: var(--text); }
  .masthead .right { display: flex; align-items: center; gap: 14px; }
  .dot { width: 8px; height: 8px; border-radius: 99px; background: var(--accent); box-shadow: 0 0 0 4px rgba(255,91,58,.18); }
  .theme-toggle {
    background: var(--card); color: var(--muted); border: 1px solid var(--line-soft);
    width: 36px; height: 36px; border-radius: 99px; cursor: pointer;
    display: inline-grid; place-items: center; padding: 0;
    transition: background .18s ease, color .18s ease, border-color .18s ease, transform .18s ease;
  }
  .theme-toggle:hover { color: var(--text); border-color: var(--line); transform: rotate(-12deg); }
  .theme-toggle svg { width: 16px; height: 16px; display: block; }
  .theme-toggle .icon-sun  { display: none; }
  .theme-toggle .icon-moon { display: block; }
  [data-theme="light"] .theme-toggle .icon-sun  { display: block; }
  [data-theme="light"] .theme-toggle .icon-moon { display: none; }

  .hero { padding: 64px 0 24px; }
  .kicker { color: var(--accent); font-size: 13px; letter-spacing: .22em; text-transform: uppercase; font-weight: 600; }
  h1.hero-title { margin: 18px 0 18px; font-size: clamp(40px, 6vw, 68px); line-height: 1.02; letter-spacing: -0.02em; font-weight: 800; }
  h1.hero-title .accent { color: var(--accent); }
  .hero-sub { font-size: 19px; color: var(--muted); max-width: 720px; line-height: 1.55; }
  .hero-sub strong { color: var(--text); font-weight: 600; }

  .bigstats {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px;
    background: var(--line-soft); border: 1px solid var(--line-soft);
    border-radius: 16px; overflow: hidden; margin-top: 44px;
  }
  .bigstat { background: var(--card); padding: 26px 24px 24px; }
  .bigstat .v { font-size: 44px; font-weight: 800; letter-spacing: -.02em; line-height: 1; }
  .bigstat .l { color: var(--muted); font-size: 12px; letter-spacing: .15em; text-transform: uppercase; margin-top: 10px; }
  .bigstat .s { color: var(--dim); font-size: 13px; margin-top: 6px; }

  section { margin-top: 80px; }
  .eyebrow { color: var(--muted); font-size: 12px; letter-spacing: .22em; text-transform: uppercase; font-weight: 600; }
  h2 { font-size: 28px; letter-spacing: -.01em; margin: 6px 0 8px; font-weight: 700; }
  .lede { color: var(--muted); font-size: 16px; max-width: 760px; }

  .timeline { margin-top: 28px; display: grid; gap: 14px; }
  .tl-card {
    background: var(--card); border: 1px solid var(--line-soft); border-radius: 14px;
    padding: 16px 18px 16px; position: relative; border-top: 3px solid var(--line);
  }
  .tl-card.fire { border-top-color: var(--accent); }
  .tl-time { color: var(--muted); font-size: 11px; letter-spacing: .16em; text-transform: uppercase; display: flex; justify-content: space-between; align-items: center; gap: 8px; }
  .tl-time .utc { color: var(--accent); font-weight: 600; letter-spacing: .08em; }
  .tl-card:not(.fire) .tl-time .utc { color: var(--muted); }
  .tl-where { font-weight: 700; font-size: 16px; margin-top: 10px; line-height: 1.3; }
  .tl-note  { color: var(--dim); font-size: 13px; margin-top: 6px; line-height: 1.5; }

  .chart-card { margin-top: 28px; background: linear-gradient(180deg, var(--card), var(--bg-2)); border: 1px solid var(--line-soft); border-radius: 18px; padding: 24px 22px 14px; box-shadow: var(--shadow); }
  .chart-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px; }
  .chart-head .t { font-weight: 700; font-size: 15px; }
  .chart-head .r { color: var(--muted); font-size: 13px; }

  .platforms { margin-top: 28px; display: grid; grid-template-columns: 1.6fr 1fr; gap: 18px; }
  .platforms .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; grid-column: 1 / -1; }
  .pcard {
    background: var(--card); border: 1px solid var(--line-soft); border-radius: 18px;
    padding: 22px 22px 22px; position: relative; overflow: hidden;
    display: block; color: inherit; text-decoration: none;
    transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
  }
  a.pcard:hover { transform: translateY(-2px); box-shadow: 0 18px 40px -20px rgba(0,0,0,.55); }
  a.pcard.hn:hover { border-color: rgba(255,102,0,.55); }
  a.pcard.x:hover  { border-color: rgba(95,180,255,.55); }
  a.pcard.rd:hover { border-color: rgba(255,69,0,.55); }
  a.pcard.elreg:hover { border-color: rgba(245,192,98,.55); }
  .pcard .arrow {
    position: absolute; bottom: 18px; right: 18px;
    width: 28px; height: 28px; border-radius: 99px; border: 1px solid var(--line);
    display: grid; place-items: center; color: var(--muted); font-size: 13px;
    background: var(--card);
    transition: transform .18s ease, color .18s ease, border-color .18s ease;
  }
  a.pcard:hover .arrow { color: var(--text); border-color: var(--text); transform: translate(2px,-2px); }
  .pcard.hn { border-color: rgba(255,102,0,.25); background: radial-gradient(700px 220px at 0% 0%, var(--hn-tint), transparent 60%), var(--card); }
  .pcard.x  { border-color: rgba(95,180,255,.22); background: radial-gradient(420px 200px at 100% 0%, var(--x-tint), transparent 60%), var(--card); }
  .pcard.rd { border-color: rgba(255,69,0,.22); background: radial-gradient(420px 200px at 0% 0%, var(--rd-tint), transparent 60%), var(--card); }
  .pcard .ph { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
  .pcard .src { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 15px; }
  .src .badge { width: 22px; height: 22px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; letter-spacing: 0; }
  .badge.hn { background: #ff6600; color: #fff; }
  .badge.rd { background: #ff4500; color: #fff; }
  .badge.x  { background: #000; color: #fff; border: 1px solid #2a3247; }
  .badge.elreg { background: #cc0000; color: #fff; }
  .pcard .when { color: var(--muted); font-size: 12px; letter-spacing: .12em; text-transform: uppercase; }
  .pcard .head { font-size: 18px; font-weight: 600; line-height: 1.35; margin: 4px 0 18px; }
  .pcard .head em { font-style: normal; color: var(--accent-soft); }
  .pcard .gridstats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
  .pmini { padding: 10px 12px; background: color-mix(in srgb, var(--text) 3%, transparent); border: 1px solid var(--line-soft); border-radius: 10px; }
  .pmini .v { font-size: 22px; font-weight: 800; letter-spacing: -.01em; line-height: 1.1; }
  .pmini .l { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .12em; margin-top: 4px; }
  .pcard .meta { color: var(--dim); font-size: 13px; margin-top: 14px; }
  .pcard .meta .who { color: var(--muted); }

  .reftable { margin-top: 22px; background: var(--card); border: 1px solid var(--line-soft); border-radius: 14px; overflow: hidden; }
  .reftable .row { display: grid; grid-template-columns: 28px 1fr auto 220px; gap: 16px; padding: 12px 18px; align-items: center; }
  .reftable .row + .row { border-top: 1px solid var(--line-soft); }
  .reftable .row.head { color: var(--muted); font-size: 11px; letter-spacing: .18em; text-transform: uppercase; padding-top: 14px; padding-bottom: 14px; }
  .reftable .ref { font-size: 14px; }
  .reftable .visits { font-weight: 700; }
  .reftable .vbar { background: var(--line-soft); height: 6px; border-radius: 99px; overflow: hidden; }
  .reftable .vbar > span { display: block; height: 100%; background: linear-gradient(90deg, var(--accent), var(--gold)); }
  .reftable .rank { color: var(--dim); font-size: 13px; }

  .audience { display: grid; grid-template-columns: 1.2fr 1fr; gap: 18px; margin-top: 28px; }
  .acard { background: var(--card); border: 1px solid var(--line-soft); border-radius: 16px; padding: 20px 22px 22px; }
  .acard h3 { margin: 0 0 14px; font-size: 15px; font-weight: 700; }
  .geo li { display: grid; grid-template-columns: 28px 1fr 56px 60px; align-items: center; gap: 10px; padding: 8px 0; border-top: 1px dashed var(--line-soft); }
  .geo li:first-child { border-top: 0; padding-top: 0; }
  .geo .flag { font-size: 18px; line-height: 1; }
  .geo .name { font-size: 14px; }
  .geo .n { text-align: right; font-weight: 700; font-size: 14px; }
  .geo .pct { text-align: right; color: var(--muted); font-size: 13px; }
  .twocol { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
  .stack { list-style: none; margin: 0; padding: 0; }
  .stack li { display: grid; grid-template-columns: 1fr 60px; align-items: center; padding: 7px 0; }
  .stack .lbl { font-size: 14px; }
  .stack .val { text-align: right; font-weight: 700; font-size: 14px; }
  .stack .meter { grid-column: 1 / -1; height: 4px; background: var(--line-soft); border-radius: 99px; margin-top: 4px; overflow: hidden; }
  .stack .meter > span { display: block; height: 100%; background: linear-gradient(90deg, var(--sky), var(--teal)); }

  .quotes { margin-top: 28px; display: grid; grid-template-columns: 1.4fr 1fr; gap: 18px; }
  .quotes .row2 { display: grid; gap: 18px; grid-column: 1 / -1; }
  .qcard {
    display: block; color: inherit; text-decoration: none;
    background: var(--card); border: 1px solid var(--line-soft); border-radius: 18px;
    padding: 22px 24px 22px; position: relative; transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
  }
  a.qcard:hover { transform: translateY(-2px); box-shadow: 0 18px 40px -20px rgba(0,0,0,.55); border-color: var(--line); }
  .qcard.featured {
    background: radial-gradient(700px 240px at 0% 0%, rgba(255,91,58,.10), transparent 60%), radial-gradient(420px 180px at 100% 100%, rgba(245,192,98,.08), transparent 60%), var(--card);
    border-color: rgba(255,91,58,.35);
  }
  .qcard .qhead { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
  .qcard .av { width: 38px; height: 38px; border-radius: 99px; flex: 0 0 38px; background: linear-gradient(135deg, var(--accent), var(--gold)); color: #fff; display: grid; place-items: center; font-weight: 800; font-size: 14px; }
  .qcard.hn .av { background: linear-gradient(135deg, #ff6600, #ff8e3a); }
  .qcard .who { line-height: 1.2; }
  .qcard .who .name { font-weight: 700; font-size: 15px; }
  .qcard .who .handle { color: var(--muted); font-size: 12px; }
  .qcard .role { margin-left: auto; font-size: 11px; padding: 4px 9px; border-radius: 99px; border: 1px solid rgba(255,91,58,.35); color: var(--accent); background: rgba(255,91,58,.08); text-transform: uppercase; letter-spacing: .12em; font-weight: 600; }
  .qcard.hn .role { color: #ff8e3a; border-color: rgba(255,142,58,.4); background: rgba(255,102,0,.08); }
  .qcard blockquote { margin: 0; font-size: 16px; line-height: 1.55; color: var(--text); padding-left: 14px; border-left: 2px solid var(--line); }
  .qcard.featured blockquote { border-left-color: var(--accent); font-size: 17px; }
  .qcard blockquote .hl { background: linear-gradient(180deg, transparent 60%, var(--hl) 60%); padding: 0 2px; }
  .qcard .qmeta { margin-top: 14px; display: flex; justify-content: space-between; align-items: center; color: var(--dim); font-size: 12px; letter-spacing: .04em; }
  .qcard .qmeta .src { color: var(--muted); }
  .qcard .qmeta .arrow { width: 24px; height: 24px; border-radius: 99px; border: 1px solid var(--line); display: inline-grid; place-items: center; color: var(--muted); font-size: 11px; transition: transform .18s ease, color .18s ease, border-color .18s ease; }
  a.qcard:hover .qmeta .arrow { color: var(--text); border-color: var(--text); transform: translate(2px,-2px); }

  .callout {
    margin-top: 28px; padding: 22px 24px;
    background: linear-gradient(180deg, rgba(255,91,58,.08), rgba(255,91,58,.02));
    border: 1px solid rgba(255,91,58,.25); border-radius: 16px;
    display: grid; grid-template-columns: auto 1fr; gap: 18px; align-items: center;
  }
  .callout .pip { width: 38px; height: 38px; border-radius: 99px; background: rgba(255,91,58,.12); display: grid; place-items: center; color: var(--accent); font-weight: 800; }
  .callout p { margin: 0; color: var(--text); font-size: 15px; line-height: 1.55; }
  .callout p span.muted { color: var(--muted); }

  footer.report-foot {
    margin-top: 80px; padding-top: 26px; border-top: 1px solid var(--line-soft);
    display: flex; justify-content: space-between; flex-wrap: wrap; gap: 14px;
    color: var(--dim); font-size: 12px; letter-spacing: .08em;
  }
  footer.report-foot a { color: var(--muted); }
  footer.report-foot a:hover { color: var(--accent-soft); }

  @media (max-width: 880px) {
    .bigstats { grid-template-columns: repeat(2, 1fr); }
    .timeline { grid-template-columns: 1fr 1fr !important; }
    .platforms { grid-template-columns: 1fr; }
    .platforms .row2 { grid-template-columns: 1fr !important; }
    .quotes { grid-template-columns: 1fr; }
    .quotes .row2 { grid-template-columns: 1fr !important; }
    .audience { grid-template-columns: 1fr; }
    .reftable .row { grid-template-columns: 24px 1fr auto; }
    .reftable .row .vbar { display: none; }
  }
