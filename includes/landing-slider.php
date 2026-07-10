<?php
declare(strict_types=1);

/** @return array<string, string> */
function hs_landing_slider_heights(array $t): array
{
    return [
        'sm' => $t['landing_slider_h_sm'] ?? 'Small (280px)',
        'md' => $t['landing_slider_h_md'] ?? 'Medium (400px)',
        'lg' => $t['landing_slider_h_lg'] ?? 'Large (520px)',
        'xl' => $t['landing_slider_h_xl'] ?? 'Full hero (70vh)',
    ];
}

/** @param array<string, mixed> $block */
function hs_landing_render_slider(array $block, array $data, callable $h): string
{
    if (empty($block['on'])) {
        return '';
    }
    $variant = (string) ($block['variant'] ?? 'fade');
    $allowed = ['fade', 'slide', 'cards', 'hero', 'thumbnails'];
    if (!in_array($variant, $allowed, true)) {
        $variant = 'fade';
    }
    $heights = hs_landing_slider_heights([]);
    $height = (string) ($block['height'] ?? 'md');
    if (!isset($heights[$height])) {
        $height = 'md';
    }
    $autoplay = !empty($block['autoplay']);
    $interval = max(2000, min(15000, (int) ($block['interval'] ?? 5000)));
    $arrows = !isset($block['arrows']) || !empty($block['arrows']);
    $dots = !isset($block['dots']) || !empty($block['dots']);

    $slides = '';
    $thumbs = '';
    $i = 0;
    $paletteHues = hs_landing_gallery_hues($data, max(6, count((array) ($block['items'] ?? []))));
    foreach ((array) ($block['items'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $title = (string) ($item['title'] ?? '');
        $sub = (string) ($item['subtitle'] ?? '');
        $cta = (string) ($item['cta_text'] ?? '');
        $ctaUrl = (string) ($item['cta_url'] ?? '#');
        $image = hs_landing_sanitize_gallery_image((string) ($item['image'] ?? ''));
        $hue = (int) ($item['hue'] ?? $paletteHues[$i] ?? 160);
        $active = $i === 0 ? ' is-active' : '';
        $bg = $image !== ''
            ? 'background-image:url(' . $h($image) . ')'
            : 'background:linear-gradient(135deg,hsl(' . $hue . ',65%,42%),hsl(' . (($hue + 40) % 360) . ',70%,55%))';
        $btn = $cta !== '' ? '<a class="btn primary" href="' . $h($ctaUrl) . '">' . $h($cta) . '</a>' : '';
        $content = ($title !== '' ? '<h2 class="slider-title">' . $h($title) . '</h2>' : '')
            . ($sub !== '' ? '<p class="slider-sub">' . $h($sub) . '</p>' : '') . $btn;

        $slides .= '<div class="slider-slide' . $active . '" data-slide="' . $h((string) $i) . '">'
            . '<div class="slider-slide-bg" style="' . $h($bg) . '"></div>'
            . ($content !== '' ? '<div class="slider-slide-content"><div class="wrap">' . $content . '</div></div>' : '')
            . '</div>';

        if ($variant === 'thumbnails') {
            $thumbBg = $image !== '' ? 'background-image:url(' . $h($image) . ')' : 'background:linear-gradient(135deg,hsl(' . $hue . ',65%,45%),hsl(' . (($hue + 30) % 360) . ',70%,55%))';
            $thumbs .= '<button type="button" class="slider-thumb' . $active . '" data-thumb="' . $h((string) $i) . '" style="' . $h($thumbBg) . '" aria-label="Slide ' . $h((string) ($i + 1)) . '"></button>';
        }
        ++$i;
        if ($i >= 12) {
            break;
        }
    }
    if ($slides === '') {
        return '';
    }

    $sec = (string) ($block['section_title'] ?? '');
    $titleHtml = $sec !== '' ? '<h2 class="sec-title slider-sec-title">' . $h($sec) . '</h2>' : '';
    $arrowPrev = $arrows ? '<button type="button" class="slider-arrow slider-prev" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>' : '';
    $arrowNext = $arrows ? '<button type="button" class="slider-arrow slider-next" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>' : '';
    $dotsHtml = $dots ? '<div class="slider-dots" data-slider-dots></div>' : '';
    $thumbsHtml = $variant === 'thumbnails' && $thumbs !== '' ? '<div class="slider-thumbs">' . $thumbs . '</div>' : '';

    return '<section class="slider-block slider-' . $h($variant) . ' slider-h-' . $h($height) . '" data-slider'
        . ' data-autoplay="' . ($autoplay ? '1' : '0') . '" data-interval="' . $h((string) $interval) . '"'
        . ' data-arrows="' . ($arrows ? '1' : '0') . '" data-dots="' . ($dots ? '1' : '0') . '">'
        . '<div class="wrap">' . $titleHtml . '</div>'
        . '<div class="slider-viewport"><div class="slider-track">' . $slides . '</div>' . $arrowPrev . $arrowNext . '</div>'
        . $thumbsHtml . $dotsHtml . '</section>';
}

function hs_landing_slider_css(): string
{
    return '.slider-block{padding:0;overflow:hidden}.slider-sec-title{padding-top:2rem;margin-bottom:1rem}'
        . '.slider-viewport{position:relative;overflow:hidden}.slider-track{position:relative}'
        . '.slider-h-sm .slider-slide{min-height:280px}.slider-h-md .slider-slide{min-height:400px}'
        . '.slider-h-lg .slider-slide{min-height:520px}.slider-h-xl .slider-slide{min-height:70vh}'
        . '.slider-slide{position:relative;display:none;min-height:inherit}.slider-slide.is-active{display:block}'
        . '.slider-fade .slider-slide.is-active{animation:sliderFade .6s ease}.slider-slide-bg{position:absolute;inset:0;background-size:cover;background-position:center}'
        . '.slider-slide-bg::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(15,23,42,.35),rgba(15,23,42,.55))}'
        . '.slider-slide-content{position:relative;z-index:1;display:flex;align-items:center;min-height:inherit;padding:2.5rem 0;color:#fff;text-align:center}'
        . '.slider-slide-content .wrap{width:100%}.slider-title{margin:0 0 .65rem;font-size:clamp(1.5rem,4vw,2.5rem);text-shadow:0 2px 12px rgba(0,0,0,.25)}'
        . '.slider-sub{margin:0 0 1.25rem;font-size:1.05rem;opacity:.92;max-width:36rem;margin-left:auto;margin-right:auto;line-height:1.6}'
        . '.slider-slide-content .btn.primary{background:#fff;color:var(--c);box-shadow:0 10px 28px rgba(0,0,0,.2)}'
        . '.slider-arrow{position:absolute;top:50%;transform:translateY(-50%);z-index:3;width:2.75rem;height:2.75rem;border:0;border-radius:999px;background:rgba(255,255,255,.92);color:var(--c);cursor:pointer;display:grid;place-items:center;box-shadow:0 8px 24px rgba(15,23,42,.15)}'
        . '.slider-prev{left:1rem}.slider-next{right:1rem}'
        . '.slider-dots{display:flex;justify-content:center;gap:.45rem;padding:1rem 0 1.5rem}'
        . '.slider-dot{width:.55rem;height:.55rem;border-radius:999px;border:0;background:#cbd5e1;cursor:pointer;padding:0}.slider-dot.is-active{background:var(--c);transform:scale(1.15)}'
        . '.slider-slide{animation:sliderSlideIn .45s ease}'
        . '.slider-cards .slider-track{display:flex;gap:1rem;overflow-x:auto;scroll-snap-type:x mandatory;padding:1.5rem 1.25rem 2rem}'
        . '.slider-cards .slider-slide{flex:0 0 min(85%,22rem);scroll-snap-align:center;border-radius:1.1rem;overflow:hidden;box-shadow:0 20px 50px rgba(15,23,42,.12);display:block}'
        . '.slider-cards .slider-slide.is-active{display:block}.slider-cards .slider-arrow{display:none}'
        . '.slider-hero .slider-slide-content{align-items:flex-end;text-align:left;padding-bottom:3rem}'
        . '.slider-hero .slider-title{font-size:clamp(2rem,5vw,3.2rem)}'
        . '.slider-thumbs{display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap;padding:0 1.25rem 1.5rem}'
        . '.slider-thumb{width:4.5rem;height:3rem;border:2px solid transparent;border-radius:.5rem;background-size:cover;background-position:center;cursor:pointer;padding:0;opacity:.65}'
        . '.slider-thumb.is-active{border-color:var(--c);opacity:1}'
        . '@keyframes sliderFade{from{opacity:0}to{opacity:1}}@keyframes sliderSlideIn{from{opacity:0;transform:translateX(12px)}to{opacity:1;transform:none}}'
        . '@media(max-width:800px){.slider-arrow{width:2.25rem;height:2.25rem}.slider-prev{left:.5rem}.slider-next{right:.5rem}}';
}

function hs_landing_slider_js(): string
{
    return '(function(){function initSlider(root){var track=root.querySelector(".slider-track");if(!track)return;var slides=[].slice.call(track.querySelectorAll(".slider-slide"));if(!slides.length)return;var variant=root.className.match(/slider-(fade|slide|cards|hero|thumbnails)/);variant=variant?variant[1]:"fade";if(variant==="cards")return;var idx=0,timer=null;var interval=parseInt(root.getAttribute("data-interval")||"5000",10);var autoplay=root.getAttribute("data-autoplay")==="1";function go(n){idx=(n+slides.length)%slides.length;slides.forEach(function(s,i){s.classList.toggle("is-active",i===idx);});var thumbs=root.querySelectorAll(".slider-thumb");thumbs.forEach(function(t,i){t.classList.toggle("is-active",i===idx);});var dots=root.querySelector("[data-slider-dots]");if(dots){dots.querySelectorAll(".slider-dot").forEach(function(d,i){d.classList.toggle("is-active",i===idx);});}}function next(){go(idx+1);}function prev(){go(idx-1);}function start(){stop();if(autoplay)timer=setInterval(next,interval);}function stop(){if(timer){clearInterval(timer);timer=null;}}var prevBtn=root.querySelector(".slider-prev");var nextBtn=root.querySelector(".slider-next");if(prevBtn)prevBtn.addEventListener("click",function(){prev();start();});if(nextBtn)nextBtn.addEventListener("click",function(){next();start();});root.querySelectorAll(".slider-thumb").forEach(function(btn){btn.addEventListener("click",function(){go(parseInt(btn.getAttribute("data-thumb")||"0",10));start();});});var dotsWrap=root.querySelector("[data-slider-dots]");if(dotsWrap&&root.getAttribute("data-dots")==="1"){slides.forEach(function(_,i){var b=document.createElement("button");b.type="button";b.className="slider-dot"+(i===0?" is-active":"");b.addEventListener("click",function(){go(i);start();});dotsWrap.appendChild(b);});}root.addEventListener("mouseenter",stop);root.addEventListener("mouseleave",start);go(0);start();}document.querySelectorAll("[data-slider]").forEach(initSlider);})();';
}