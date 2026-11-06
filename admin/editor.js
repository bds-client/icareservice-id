/* Branch and pricelist editors: edit the JSON in the browser, send it with the form on save. */
(function () {
  var cfg = window.EDITOR, data = cfg.data, root = document.getElementById('editor');
  document.getElementById('json-form').addEventListener('submit', function () {
    document.getElementById('json-field').value = JSON.stringify(data);
  });

  function el(tag, attrs, kids) {
    var n = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) {
      if (k === 'text') n.textContent = attrs[k];
      else if (k.slice(0, 2) === 'on') n.addEventListener(k.slice(2), attrs[k]);
      else n.setAttribute(k, attrs[k]);
    });
    (kids || []).forEach(function (c) { if (c) n.appendChild(c); });
    return n;
  }
  function btn(text, fn, cls) { return el('button', { type: 'button', class: 'btn small ' + (cls || ''), text: text, onclick: fn }); }
  function field(label, obj, key, placeholder) {
    return el('label', { text: label }, [el('input', { value: obj[key] == null ? '' : obj[key], placeholder: placeholder || '', oninput: function (e) { obj[key] = e.target.value; } })]);
  }
  function move(arr, i, d) { var j = i + d; if (j < 0 || j >= arr.length) return; var x = arr[i]; arr[i] = arr[j]; arr[j] = x; }

  /* ---------- Branches ---------- */
  function renderBranches() {
    root.innerHTML = '';
    data.forEach(function (b, i) {
      root.appendChild(el('div', { class: 'card' }, [
        el('div', { class: 'row' }, [el('strong', { text: 'Cabang ' + (i + 1) }), el('span', { style: 'flex:1' }),
          btn('↑', function () { move(data, i, -1); renderBranches(); }), btn('↓', function () { move(data, i, 1); renderBranches(); }),
          btn('Hapus', function () { if (confirm('Hapus cabang ini?')) { data.splice(i, 1); renderBranches(); } }, 'danger')]),
        el('div', { class: 'grid2' }, [
          field('Label (ID)', b, 'tag'), field('Label (EN)', b, 'tag_en', 'kosong = sama'),
          field('Nama (ID)', b, 'name'), field('Nama (EN)', b, 'name_en', 'kosong = sama'),
          field('Jalan (ID)', b, 'street'), field('Jalan (EN)', b, 'street_en', 'kosong = sama'),
          field('Daerah (ID)', b, 'area'), field('Daerah (EN)', b, 'area_en', 'kosong = sama'),
          field('Nomor telepon', b, 'phone', 'kosong = nomor utama'), field('WhatsApp (62…)', b, 'whatsapp', 'kosong = WhatsApp utama'),
        ]),
        field('Link Google Maps', b, 'maps', 'https://maps.app.goo.gl/…'),
      ]));
    });
    root.appendChild(btn('+ Tambah cabang', function () {
      data.push({ tag: 'DENPASAR ' + (data.length + 1), name: 'iCare Service', street: '', area: '', phone: '', whatsapp: '', maps: '' });
      renderBranches();
    }));
  }

  /* ---------- Pricelist ---------- */
  var dIdx = 0, rIdx = 0;
  function blankRepair() {
    return { name: 'Perbaikan baru', title: '', eyebrow: '', first: 'Model', cols: [{ label: 'Harga', warranty: '' }], rows: [{ model: '', prices: [null] }] };
  }
  function renderPricelist() {
    root.innerHTML = '';
    var dev = data[dIdx];
    root.appendChild(el('h3', { text: 'Perangkat' }));
    root.appendChild(el('div', { class: 'pills' }, data.map(function (d, i) {
      return btn(d.name + ' (' + d.repairs.length + ')', function () { dIdx = i; rIdx = 0; renderPricelist(); }, i === dIdx ? 'on' : '');
    })));
    if (!dev) return;
    root.appendChild(el('h3', { text: 'Jenis perbaikan ' + dev.name }));
    root.appendChild(el('div', { class: 'pills' }, dev.repairs.map(function (r, i) {
      return btn(r.name, function () { rIdx = i; renderPricelist(); }, i === rIdx ? 'on' : '');
    }).concat([btn('+ Tambah perbaikan', function () { dev.repairs.push(blankRepair()); rIdx = dev.repairs.length - 1; renderPricelist(); })])));
    if (!dev.repairs.length) { root.appendChild(el('p', { class: 'muted', text: 'Belum ada perbaikan. Pengunjung melihat pesan “belum tersedia”.' })); return; }
    var r = dev.repairs[rIdx];
    root.appendChild(el('div', { class: 'card' }, [
      el('div', { class: 'row' }, [el('strong', { text: 'Detail perbaikan' }), el('span', { style: 'flex:1' }),
        btn('↑', function () { move(dev.repairs, rIdx, -1); rIdx = Math.max(0, rIdx - 1); renderPricelist(); }),
        btn('↓', function () { move(dev.repairs, rIdx, 1); rIdx = Math.min(dev.repairs.length - 1, rIdx + 1); renderPricelist(); }),
        btn('Hapus perbaikan', function () { if (confirm('Hapus perbaikan ini beserta harganya?')) { dev.repairs.splice(rIdx, 1); rIdx = 0; renderPricelist(); } }, 'danger')]),
      el('div', { class: 'grid2' }, [
        field('Nama tombol (ID)', r, 'name'), field('Nama tombol (EN)', r, 'name_en', 'kosong = terjemahan otomatis'),
        field('Judul tabel (ID)', r, 'title'), field('Judul tabel (EN)', r, 'title_en', 'kosong = terjemahan otomatis'),
        field('Teks kecil di atas judul (ID)', r, 'eyebrow'), field('Teks kecil (EN)', r, 'eyebrow_en', 'kosong = terjemahan otomatis'),
        field('Judul kolom pertama (ID)', r, 'first'), field('Judul kolom pertama (EN)', r, 'first_en', 'kosong = terjemahan otomatis'),
      ]),
    ]));
    var head = el('tr', {}, [el('th', { text: 'Model' })]);
    r.cols.forEach(function (c, ci) {
      head.appendChild(el('th', {}, [
        el('input', { value: c.label || '', placeholder: 'Kolom (ID)', oninput: function (e) { c.label = e.target.value; } }),
        el('input', { value: c.label_en || '', placeholder: 'Kolom (EN)', oninput: function (e) { c.label_en = e.target.value; } }),
        el('input', { value: c.warranty || '', placeholder: 'Garansi (ID)', oninput: function (e) { c.warranty = e.target.value; } }),
        el('input', { value: c.warranty_en || '', placeholder: 'Garansi (EN)', oninput: function (e) { c.warranty_en = e.target.value; } }),
        btn('Hapus kolom', function () { if (confirm('Hapus kolom ini?')) { r.cols.splice(ci, 1); r.rows.forEach(function (row) { row.prices.splice(ci, 1); }); renderPricelist(); } }, 'danger'),
      ]));
    });
    head.appendChild(el('th', {}, [btn('+ Kolom', function () { r.cols.push({ label: 'Pilihan', warranty: '' }); r.rows.forEach(function (row) { row.prices.push(null); }); renderPricelist(); })]));
    var body = el('tbody');
    r.rows.forEach(function (row, ri) {
      var tr = el('tr', {}, [el('td', {}, [el('input', { value: row.model, placeholder: 'Nama model', oninput: function (e) { row.model = e.target.value; } })])]);
      r.cols.forEach(function (_, ci) {
        tr.appendChild(el('td', {}, [el('input', { value: row.prices[ci] == null ? '' : row.prices[ci], placeholder: '—', oninput: function (e) { row.prices[ci] = e.target.value.trim() === '' ? null : e.target.value; } })]));
      });
      tr.appendChild(el('td', {}, [btn('↑', function () { move(r.rows, ri, -1); renderPricelist(); }), btn('↓', function () { move(r.rows, ri, 1); renderPricelist(); }), btn('✕', function () { r.rows.splice(ri, 1); renderPricelist(); }, 'danger')]));
      body.appendChild(tr);
    });
    root.appendChild(el('div', { class: 'scroll' }, [el('table', { class: 'ed' }, [el('thead', {}, [head]), body])]));
    root.appendChild(el('p', {}, [btn('+ Tambah model', function () { r.rows.push({ model: '', prices: r.cols.map(function () { return null; }) }); renderPricelist(); })]));
  }

  if (cfg.type === 'branches') renderBranches(); else renderPricelist();
})();
