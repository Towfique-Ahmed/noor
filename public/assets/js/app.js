/* Noor — progressive enhancement only. Every page works without this file. */
(function () {
  'use strict';

  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

  /* ---------- Theme ---------- */

  var storedTheme = null;
  try { storedTheme = localStorage.getItem('noor-theme'); } catch (e) { /* private mode */ }
  if (storedTheme) { document.documentElement.setAttribute('data-theme', storedTheme); }

  var themeToggle = $('.theme-toggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', function () {
      var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      var current = document.documentElement.getAttribute('data-theme') || (prefersDark ? 'dark' : 'light');
      var next = current === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      try { localStorage.setItem('noor-theme', next); } catch (e) { /* ignore */ }
    });
  }

  /* ---------- Mobile navigation ---------- */

  var navToggle = $('.nav-toggle');
  var nav = $('#site-nav');
  if (navToggle && nav) {
    navToggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      navToggle.setAttribute('aria-expanded', String(open));
    });
  }

  /* ---------- Next prayer highlight ---------- */

  $$('[data-next-prayer]').forEach(function (list) {
    var items = $$('[data-time]', list);
    if (!items.length) { return; }

    var now = new Date();
    var minutesNow = now.getHours() * 60 + now.getMinutes();
    var next = null;

    items.forEach(function (item) {
      var parts = (item.getAttribute('data-time') || '').split(':');
      var minutes = parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
      if (isNaN(minutes)) { return; }
      if (next === null && minutes > minutesNow && item.getAttribute('data-prayer') !== 'Sunrise') {
        next = { el: item, minutes: minutes, name: item.getAttribute('data-prayer') };
      }
    });

    if (!next) {
      var first = items[0];
      var firstParts = (first.getAttribute('data-time') || '').split(':');
      next = {
        el: first,
        minutes: parseInt(firstParts[0], 10) * 60 + parseInt(firstParts[1], 10) + 1440,
        name: first.getAttribute('data-prayer')
      };
    }

    next.el.classList.add('is-next');

    var label = $('[data-next-label]', list.parentNode);
    if (label) {
      var left = next.minutes - minutesNow;
      var hours = Math.floor(left / 60);
      var mins = left % 60;
      label.textContent = 'Next: ' + next.name + ' in ' +
        (hours > 0 ? hours + ' h ' : '') + mins + ' min';
    }
  });

  /* ---------- Client-side list filters ---------- */

  $$('[data-filter]').forEach(function (input) {
    var targetSelector = input.getAttribute('data-filter');
    input.addEventListener('input', function () {
      var term = input.value.trim().toLowerCase();
      $$(targetSelector).forEach(function (card) {
        var holder = card.closest('li') || card;
        var match = term === '' || card.textContent.toLowerCase().indexOf(term) !== -1;
        holder.classList.toggle('is-hidden', !match);
      });
    });
  });

  /* ---------- Qibla: geolocation and compass ---------- */

  var locateButton = $('[data-locate]');
  if (locateButton && navigator.geolocation) {
    locateButton.addEventListener('click', function () {
      locateButton.disabled = true;
      locateButton.textContent = 'Locating…';
      navigator.geolocation.getCurrentPosition(function (position) {
        var lat = $('#lat');
        var lng = $('#lng');
        if (lat && lng) {
          lat.value = position.coords.latitude.toFixed(6);
          lng.value = position.coords.longitude.toFixed(6);
          lat.form.submit();
        }
      }, function () {
        locateButton.disabled = false;
        locateButton.textContent = 'Use my location';
        var hint = $('[data-compass-hint]');
        if (hint) { hint.textContent = 'Location permission was denied. Enter your coordinates above instead.'; }
      }, { enableHighAccuracy: true, timeout: 10000 });
    });
  } else if (locateButton) {
    locateButton.disabled = true;
  }

  var compass = $('[data-compass]');
  var needle = $('[data-needle]');
  if (compass && needle) {
    var qibla = parseFloat(compass.getAttribute('data-qibla')) || 0;
    needle.style.transform = 'rotate(' + qibla + 'deg)';

    var enableButton = $('[data-compass-enable]');
    var heading = 0;

    var onOrientation = function (event) {
      var alpha = event.webkitCompassHeading !== undefined
        ? event.webkitCompassHeading
        : (event.alpha !== null ? 360 - event.alpha : null);
      if (alpha === null || isNaN(alpha)) { return; }
      heading = alpha;
      needle.style.transform = 'rotate(' + (qibla - heading) + 'deg)';
      var face = $('.compass-face');
      if (face) { face.style.transform = 'rotate(' + (-heading) + 'deg)'; }
    };

    if (enableButton) {
      enableButton.addEventListener('click', function () {
        var hint = $('[data-compass-hint]');
        var start = function () {
          window.addEventListener('deviceorientationabsolute', onOrientation, true);
          window.addEventListener('deviceorientation', onOrientation, true);
          enableButton.disabled = true;
          enableButton.textContent = 'Compass on';
        };

        if (typeof DeviceOrientationEvent !== 'undefined' &&
            typeof DeviceOrientationEvent.requestPermission === 'function') {
          DeviceOrientationEvent.requestPermission().then(function (state) {
            if (state === 'granted') {
              start();
            } else if (hint) {
              hint.textContent = 'Compass permission was denied. Use the bearing reading instead.';
            }
          }).catch(function () {
            if (hint) { hint.textContent = 'This device did not allow compass access.'; }
          });
        } else if (window.DeviceOrientationEvent) {
          start();
        } else if (hint) {
          hint.textContent = 'This device has no compass. Line up the bearing with a magnetic compass instead.';
        }
      });
    }
  }

  /* ---------- Tasbih counter ---------- */

  var tasbih = $('[data-tasbih]');
  if (tasbih) {
    var select = $('[data-dhikr-select]', tasbih);
    var button = $('[data-count-button]', tasbih);
    var countEl = $('[data-count]', tasbih);
    var echoEl = $('[data-count-echo]', tasbih);
    var targetEcho = $('[data-target-echo]', tasbih);
    var roundsEl = $('[data-rounds]', tasbih);
    var progress = $('[data-progress]', tasbih);
    var arabicEl = $('[data-dhikr-arabic]', tasbih);
    var meaningEl = $('[data-dhikr-meaning]', tasbih);

    var state = { count: 0, rounds: 0, index: 0 };
    try {
      var saved = JSON.parse(localStorage.getItem('noor-tasbih') || 'null');
      if (saved && typeof saved.count === 'number') { state = saved; }
    } catch (e) { /* ignore */ }

    var save = function () {
      try { localStorage.setItem('noor-tasbih', JSON.stringify(state)); } catch (e) { /* ignore */ }
    };

    var currentOption = function () { return select.options[select.selectedIndex]; };

    var render = function () {
      var option = currentOption();
      var target = parseInt(option.getAttribute('data-target'), 10) || 33;
      countEl.textContent = String(state.count);
      echoEl.textContent = String(state.count);
      targetEcho.textContent = String(target);
      roundsEl.textContent = String(state.rounds);
      progress.style.width = Math.min(100, (state.count / target) * 100) + '%';
      arabicEl.innerHTML = option.getAttribute('data-arabic');
      meaningEl.textContent = option.getAttribute('data-meaning');
    };

    if (state.index >= 0 && state.index < select.options.length) {
      select.selectedIndex = state.index;
    }

    select.addEventListener('change', function () {
      state.index = select.selectedIndex;
      state.count = 0;
      save();
      render();
    });

    button.addEventListener('click', function () {
      var target = parseInt(currentOption().getAttribute('data-target'), 10) || 33;
      state.count += 1;
      if (state.count >= target) {
        state.rounds += 1;
        state.count = 0;
        if (navigator.vibrate) { navigator.vibrate([30, 40, 30]); }
      } else if (navigator.vibrate) {
        navigator.vibrate(15);
      }
      save();
      render();
    });

    var undo = $('[data-undo]', tasbih);
    if (undo) {
      undo.addEventListener('click', function () {
        if (state.count > 0) {
          state.count -= 1;
        } else if (state.rounds > 0) {
          state.rounds -= 1;
          state.count = (parseInt(currentOption().getAttribute('data-target'), 10) || 33) - 1;
        }
        save();
        render();
      });
    }

    var reset = $('[data-reset]', tasbih);
    if (reset) {
      reset.addEventListener('click', function () {
        state.count = 0;
        state.rounds = 0;
        save();
        render();
      });
    }

    render();
  }
}());
