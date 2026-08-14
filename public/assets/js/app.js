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

  /* ---------- Tabs ---------- */

  $$('[data-tab-target]').forEach(function (tab) {
    tab.addEventListener('click', function () {
      var group = tab.parentNode;
      $$('[data-tab-target]', group).forEach(function (other) {
        var panel = $(other.getAttribute('data-tab-target'));
        var on = other === tab;
        other.classList.toggle('is-active', on);
        other.setAttribute('aria-selected', String(on));
        if (panel) { panel.classList.toggle('is-active', on); }
      });
    });
  });

  /* ---------- Stored reading state ---------- */

  var store = {
    read: function (key, fallback) {
      try { return JSON.parse(localStorage.getItem(key) || 'null') || fallback; }
      catch (e) { return fallback; }
    },
    write: function (key, value) {
      try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) { /* ignore */ }
    }
  };

  var bookmarkKey = 'noor-bookmarks';
  var lastReadKey = 'noor-last-read';

  var bookmarkId = function (surah, ayah) { return surah + ':' + ayah; };

  /* ---------- Qur'an reader ---------- */

  var reader = $('[data-reader]');
  if (reader) {
    var surahNo = reader.getAttribute('data-surah');
    var surahName = reader.getAttribute('data-surah-name');
    var ayahs = $$('.ayah', reader);
    var bookmarks = store.read(bookmarkKey, []);

    store.write(lastReadKey, { surah: surahNo, name: surahName, at: Date.now() });

    // Mark ayahs that are already bookmarked.
    ayahs.forEach(function (ayah) {
      var id = bookmarkId(surahNo, ayah.getAttribute('data-ayah'));
      var saved = bookmarks.some(function (b) { return b.id === id; });
      ayah.classList.toggle('is-bookmarked', saved);
      var star = $('[data-bookmark]', ayah);
      if (star) {
        star.classList.toggle('is-on', saved);
        star.textContent = saved ? '\u2605' : '\u2606';
      }
    });

    // Bookmark and copy.
    ayahs.forEach(function (ayah) {
      var number = ayah.getAttribute('data-ayah');
      var id = bookmarkId(surahNo, number);

      var star = $('[data-bookmark]', ayah);
      if (star) {
        star.addEventListener('click', function () {
          bookmarks = store.read(bookmarkKey, []);
          var at = bookmarks.findIndex(function (b) { return b.id === id; });
          if (at === -1) {
            var translationEl = $('[data-translation]', ayah);
            bookmarks.push({
              id: id,
              surah: surahNo,
              ayah: number,
              name: surahName,
              text: translationEl ? translationEl.textContent.slice(0, 160) : ''
            });
          } else {
            bookmarks.splice(at, 1);
          }
          store.write(bookmarkKey, bookmarks);
          var on = at === -1;
          ayah.classList.toggle('is-bookmarked', on);
          star.classList.toggle('is-on', on);
          star.textContent = on ? '\u2605' : '\u2606';
        });
      }

      var copy = $('[data-copy]', ayah);
      if (copy) {
        copy.addEventListener('click', function () {
          var arabicEl = $('[data-arabic]', ayah);
          var translationEl = $('[data-translation]', ayah);
          var text = (arabicEl ? arabicEl.textContent.trim() + '\n\n' : '') +
            (translationEl ? translationEl.textContent.trim() + '\n\n' : '') +
            '— Qur\'an ' + surahNo + ':' + number;
          var done = function () {
            copy.textContent = '\u2713';
            setTimeout(function () { copy.textContent = '\uD83D\uDCCB'; }, 1200);
          };
          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done, function () { /* ignore */ });
          }
        });
      }
    });

    // Expand or collapse every tafsir at once.
    var toggleAll = $('[data-toggle-all-tafsir]');
    if (toggleAll) {
      var allOpen = false;
      var panels = $$('.tafsir', reader);
      if (!panels.length) {
        toggleAll.disabled = true;
      }
      toggleAll.addEventListener('click', function () {
        allOpen = !allOpen;
        panels.forEach(function (panel) { panel.open = allOpen; });
        toggleAll.textContent = allOpen ? 'Hide all tafsir' : 'Show all tafsir';
      });
    }

    /* ----- Recitation player ----- */

    var player = $('[data-player]');
    if (player && ayahs.length) {
      var audio = new Audio();
      var current = -1;
      var toggleBtn = $('[data-player-toggle]', player);
      var progress = $('[data-player-progress]', player);
      var titleEl = $('[data-player-title]', player);
      var repeatEl = $('[data-player-repeat]', player);
      var speedEl = $('[data-player-speed]', player);

      var setIcon = function (playing) {
        toggleBtn.innerHTML = playing ? '&#10073;&#10073;' : '&#9654;';
      };

      var highlight = function (index) {
        ayahs.forEach(function (a, i) { a.classList.toggle('is-playing', i === index); });
      };

      var load = function (index, autoplay) {
        if (index < 0 || index >= ayahs.length) { return; }
        current = index;
        var ayah = ayahs[index];
        audio.src = ayah.getAttribute('data-audio');
        audio.playbackRate = parseFloat(speedEl.value) || 1;
        titleEl.textContent = surahName + ' ' + surahNo + ':' + ayah.getAttribute('data-ayah');
        highlight(index);
        player.hidden = false;
        if (autoplay) {
          audio.play().then(function () { setIcon(true); }, function () { setIcon(false); });
        }
      };

      $$('[data-play-ayah]').forEach(function (button, index) {
        button.addEventListener('click', function () {
          if (index === current && !audio.paused) {
            audio.pause();
            setIcon(false);
            return;
          }
          load(index, true);
          ayahs[index].scrollIntoView({ block: 'center', behavior: 'smooth' });
        });
      });

      toggleBtn.addEventListener('click', function () {
        if (current === -1) { load(0, true); return; }
        if (audio.paused) {
          audio.play().then(function () { setIcon(true); }, function () { /* ignore */ });
        } else {
          audio.pause();
          setIcon(false);
        }
      });

      $('[data-player-next]', player).addEventListener('click', function () {
        load(Math.min(current + 1, ayahs.length - 1), true);
      });
      $('[data-player-prev]', player).addEventListener('click', function () {
        load(Math.max(current - 1, 0), true);
      });
      $('[data-player-close]', player).addEventListener('click', function () {
        audio.pause();
        setIcon(false);
        highlight(-1);
        player.hidden = true;
      });

      speedEl.addEventListener('change', function () {
        audio.playbackRate = parseFloat(speedEl.value) || 1;
      });

      audio.addEventListener('timeupdate', function () {
        if (audio.duration) {
          progress.style.width = ((audio.currentTime / audio.duration) * 100) + '%';
        }
      });

      audio.addEventListener('ended', function () {
        if (repeatEl.checked) { load(current, true); return; }
        if (current + 1 < ayahs.length) {
          load(current + 1, true);
          ayahs[current].scrollIntoView({ block: 'center', behavior: 'smooth' });
        } else {
          setIcon(false);
          highlight(-1);
        }
      });

      audio.addEventListener('error', function () {
        titleEl.textContent = 'This recitation could not be loaded.';
        setIcon(false);
      });

      audio.addEventListener('pause', function () { setIcon(false); });
      audio.addEventListener('play', function () { setIcon(true); });
    }
  }

  /* ---------- Bookmarks page ---------- */

  var bookmarksPage = $('[data-bookmarks]');
  if (bookmarksPage) {
    var listEl = $('[data-bookmark-list]', bookmarksPage);
    var emptyEl = $('[data-bookmark-empty]', bookmarksPage);
    var clearBtn = $('[data-clear-bookmarks]', bookmarksPage);
    var readerUrl = bookmarksPage.getAttribute('data-reader-url');

    var renderBookmarks = function () {
      var saved = store.read(bookmarkKey, []);
      listEl.innerHTML = '';
      emptyEl.hidden = saved.length > 0;
      clearBtn.hidden = saved.length === 0;

      saved.forEach(function (item) {
        var li = document.createElement('li');

        var left = document.createElement('div');
        var ref = document.createElement('div');
        ref.className = 'bm-ref';
        ref.textContent = (item.name || 'Surah ' + item.surah) + ' ' + item.surah + ':' + item.ayah;
        var text = document.createElement('div');
        text.className = 'bm-text';
        text.textContent = item.text || '';
        left.appendChild(ref);
        left.appendChild(text);

        var open = document.createElement('a');
        open.className = 'btn btn-ghost btn-sm';
        var join = readerUrl.indexOf('?') === -1 ? '?' : '&';
        open.href = readerUrl + join + 'surah=' + encodeURIComponent(item.surah) + '#ayah-' + encodeURIComponent(item.ayah);
        open.textContent = 'Open';

        li.appendChild(left);
        li.appendChild(open);
        listEl.appendChild(li);
      });
    };

    clearBtn.addEventListener('click', function () {
      store.write(bookmarkKey, []);
      renderBookmarks();
    });

    renderBookmarks();
  }

  /* ---------- Continue reading ---------- */

  (function () {
    var note = $('[data-last-read-note]');
    var link = $('[data-last-read-link]');
    if (!note || !link) { return; }

    var last = store.read(lastReadKey, null);
    if (!last || !last.surah) { return; }

    var holder = $('[data-bookmarks]');
    var base = note.getAttribute('data-reader-url')
      || (holder && holder.getAttribute('data-reader-url'))
      || link.getAttribute('href')
      || '?page=quran';

    var join = base.indexOf('?') === -1 ? '?' : '&';
    link.href = base + join + 'surah=' + encodeURIComponent(last.surah);
    link.textContent = 'Continue ' + (last.name || 'Surah ' + last.surah);
    note.hidden = false;

    var emptyNote = $('[data-last-read-empty]');
    if (emptyNote) { emptyNote.hidden = true; }
  }());

  /* ---------- Prayer countdown ---------- */

  var countdown = $('[data-countdown]');
  if (countdown) {
    var list = $('[data-next-prayer]');
    var ring = $('[data-ring]', countdown);
    var nameEl = $('[data-countdown-name]', countdown);
    var valueEl = $('[data-countdown-value]', countdown);
    var atEl = $('[data-countdown-at]', countdown);
    var notifyBtn = $('[data-notify-toggle]');
    var notifyOn = false;
    var fired = '';

    var schedule = function () {
      if (!list) { return null; }
      var now = new Date();
      var items = $$('[data-time]', list).filter(function (item) {
        return item.getAttribute('data-prayer') !== 'Sunrise';
      });
      if (!items.length) { return null; }

      var stops = items.map(function (item) {
        var parts = (item.getAttribute('data-time') || '').split(':');
        var when = new Date(now);
        when.setHours(parseInt(parts[0], 10), parseInt(parts[1], 10), 0, 0);
        return { name: item.getAttribute('data-prayer'), when: when };
      });

      for (var i = 0; i < stops.length; i++) {
        if (stops[i].when > now) {
          var previous = i === 0 ? null : stops[i - 1].when;
          return { next: stops[i], previous: previous, total: stops.length };
        }
      }
      var tomorrow = new Date(stops[0].when);
      tomorrow.setDate(tomorrow.getDate() + 1);
      return { next: { name: stops[0].name, when: tomorrow }, previous: stops[stops.length - 1].when, total: stops.length };
    };

    var chime = function () {
      try {
        var Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) { return; }
        var ctx = new Ctx();
        [0, 0.35, 0.7].forEach(function (offset) {
          var osc = ctx.createOscillator();
          var gain = ctx.createGain();
          osc.type = 'sine';
          osc.frequency.value = 528;
          gain.gain.setValueAtTime(0.0001, ctx.currentTime + offset);
          gain.gain.exponentialRampToValueAtTime(0.25, ctx.currentTime + offset + 0.05);
          gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + offset + 0.3);
          osc.connect(gain).connect(ctx.destination);
          osc.start(ctx.currentTime + offset);
          osc.stop(ctx.currentTime + offset + 0.32);
        });
      } catch (e) { /* audio unavailable */ }
    };

    var tick = function () {
      var plan = schedule();
      if (!plan) { return; }

      var now = new Date();
      var left = Math.max(0, plan.next.when - now);
      var hours = Math.floor(left / 3600000);
      var minutes = Math.floor((left % 3600000) / 60000);
      var seconds = Math.floor((left % 60000) / 1000);

      nameEl.textContent = 'Next: ' + plan.next.name;
      valueEl.textContent = (hours > 0 ? hours + 'h ' : '') + minutes + 'm ' + seconds + 's';
      atEl.textContent = 'at ' + plan.next.when.toTimeString().slice(0, 5);

      if (plan.previous) {
        var span = plan.next.when - plan.previous;
        var gone = now - plan.previous;
        var fraction = span > 0 ? Math.min(1, Math.max(0, gone / span)) : 0;
        ring.style.strokeDashoffset = String(327 - (327 * fraction));
      }

      var stamp = plan.next.name + plan.next.when.toDateString();
      if (left <= 1000 && notifyOn && fired !== stamp) {
        fired = stamp;
        chime();
        if (window.Notification && Notification.permission === 'granted') {
          new Notification('It is time for ' + plan.next.name);
        }
      }
    };

    tick();
    setInterval(tick, 1000);

    if (notifyBtn) {
      if (!window.Notification) {
        notifyBtn.textContent = 'Reminders are not supported here';
        notifyBtn.disabled = true;
      } else {
        notifyBtn.addEventListener('click', function () {
          if (notifyOn) {
            notifyOn = false;
            notifyBtn.textContent = 'Remind me at the next prayer';
            return;
          }
          Notification.requestPermission().then(function (state) {
            notifyOn = state === 'granted';
            notifyBtn.textContent = notifyOn
              ? 'Reminder on — keep this tab open'
              : 'Notifications were blocked';
          });
        });
      }
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
