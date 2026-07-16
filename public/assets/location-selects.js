document.querySelectorAll('[data-location-form]').forEach((form) => {
  const endpoint = form.dataset.locationEndpoint;
  const region = form.querySelector('[name="region_code"]');
  const province = form.querySelector('[name="province_id"]');
  const locality = form.querySelector('[name="locality_id"]');
  const barangay = form.querySelector('[name="barangay_id"]');
  const postal = form.querySelector('[name="zip_code"], [name="postal_code"]');
  const initialPostal = postal?.value.trim() || '';
  const locationStatus = form.querySelector('[data-location-status]');
  const postalStatus = form.querySelector('[data-postal-status]');
  if (!endpoint || !region || !province || !locality || !barangay) return;

  const cache = new Map();
  const selectLabels = new Map([
    [region, 'Region'], [province, 'Province / District'],
    [locality, 'City / Municipality'], [barangay, 'Barangay'],
  ]);

  let postalList = null;
  if (postal && postal.tagName === 'INPUT') {
    const listId = `${postal.id || postal.name}-options`;
    postalList = form.querySelector(`#${CSS.escape(listId)}`) || document.createElement('datalist');
    postalList.id = listId;
    if (!postalList.isConnected) postal.insertAdjacentElement('afterend', postalList);
    postal.setAttribute('list', listId);
    postal.setAttribute('inputmode', 'numeric');
    postal.setAttribute('maxlength', '4');
    postal.setAttribute('pattern', '\\d{4}');
  }

  const announce = (message, type = '') => {
    if (!locationStatus) return;
    locationStatus.textContent = message;
    locationStatus.dataset.state = type;
  };

  const setState = (select, state) => {
    const field = select.closest('[data-location-step]');
    if (field) field.dataset.state = state;
    select.setAttribute('aria-busy', String(state === 'loading'));
  };

  const reset = (select, label) => {
    select.replaceChildren(new Option(label, ''));
    select.disabled = true;
    setState(select, 'locked');
  };

  const request = async (url) => {
    if (cache.has(url)) return cache.get(url);
    const pending = fetch(url, { headers: { Accept: 'application/json' } }).then(async (response) => {
      if (!response.ok) throw new Error('Location request failed');
      return response.json();
    });
    cache.set(url, pending);
    try {
      return await pending;
    } catch (error) {
      cache.delete(url);
      throw error;
    }
  };

  const load = async (select, url, label) => {
    reset(select, `Loading ${selectLabels.get(select).toLowerCase()}…`);
    setState(select, 'loading');
    const data = await request(url);
    select.replaceChildren(new Option(label, ''));
    data.items.forEach((item) => select.add(new Option(item.name, item.id)));
    select.disabled = false;
    setState(select, 'ready');
  };

  const resetPostal = (message = 'Choose your city and barangay to find the postal code.', preserveValue = false) => {
    if (!postal) return;
    if (!preserveValue) postal.value = '';
    postal.readOnly = true;
    postal.dataset.state = 'waiting';
    if (postalList) postalList.replaceChildren();
    if (postalStatus) postalStatus.textContent = message;
  };

  const loadPostal = async () => {
    if (!postal || !locality.value) {
      resetPostal();
      return;
    }

    const existingPostal = postal.value.trim() || initialPostal;
    postal.readOnly = true;
    postal.dataset.state = 'loading';
    if (postalStatus) postalStatus.textContent = 'Finding the postal code…';
    const params = new URLSearchParams({ postal_locality_id: locality.value });
    if (barangay.value) params.set('barangay_id', barangay.value);

    try {
      const data = await request(`${endpoint}?${params}`);
      if (postalList) {
        postalList.replaceChildren(...data.items.map((item) => {
          const option = document.createElement('option');
          option.value = item.code;
          option.label = `${item.label} — ${item.code}`;
          return option;
        }));
      }

      postal.readOnly = false;
      if (data.recommended) {
        postal.value = data.recommended;
        postal.dataset.state = 'complete';
        if (postalStatus) postalStatus.textContent = data.match === 'barangay'
          ? 'Auto-filled from your barangay. You can correct it if needed.'
          : 'Auto-filled from your city or municipality. You can correct it if needed.';
      } else if (data.items.length > 1) {
        postal.value = /^\d{4}$/.test(existingPostal) ? existingPostal : '';
        postal.dataset.state = 'choice';
        if (postalStatus) postalStatus.textContent = `${data.items.length} postal codes serve this city. Type or choose the code for your delivery area.`;
      } else {
        postal.value = /^\d{4}$/.test(existingPostal) ? existingPostal : '';
        postal.dataset.state = 'manual';
        if (postalStatus) postalStatus.textContent = 'No unambiguous match was found. Enter the 4-digit postal code manually.';
      }
    } catch (error) {
      postal.readOnly = false;
      postal.dataset.state = 'manual';
      if (postalStatus) postalStatus.textContent = 'Postal lookup is unavailable. Enter the 4-digit code manually.';
    }
  };

  const handleFailure = (select) => {
    reset(select, `${selectLabels.get(select)} unavailable`);
    setState(select, 'error');
    announce('We could not load the next address step. Check your connection and try again.', 'error');
  };

  region.addEventListener('change', async () => {
    reset(province, 'Select a region first');
    reset(locality, 'Select a province first');
    reset(barangay, 'Select a city first');
    resetPostal();
    if (!region.value) return announce('Start by choosing your region.');
    setState(region, 'complete');
    announce('Loading provinces and districts…');
    try {
      await load(province, `${endpoint}?region_code=${encodeURIComponent(region.value)}`, 'Choose a province / district');
      announce('Region selected. Now choose your province or district.');
    } catch (error) { handleFailure(province); }
  });

  province.addEventListener('change', async () => {
    reset(locality, 'Select a province first');
    reset(barangay, 'Select a city first');
    resetPostal();
    if (!province.value) return announce('Choose your province or district.');
    setState(province, 'complete');
    announce('Loading cities and municipalities…');
    try {
      await load(locality, `${endpoint}?province_id=${encodeURIComponent(province.value)}`, 'Choose a city / municipality');
      announce('Province selected. Now choose your city or municipality.');
    } catch (error) { handleFailure(locality); }
  });

  locality.addEventListener('change', async () => {
    reset(barangay, 'Select a city first');
    resetPostal();
    if (!locality.value) return announce('Choose your city or municipality.');
    setState(locality, 'complete');
    announce('Loading barangays…');
    try {
      await Promise.all([
        load(barangay, `${endpoint}?locality_id=${encodeURIComponent(locality.value)}`, 'Choose a barangay'),
        loadPostal(),
      ]);
      announce('City selected. Finish by choosing your barangay.');
    } catch (error) { handleFailure(barangay); }
  });

  barangay.addEventListener('change', async () => {
    if (!barangay.value) {
      setState(barangay, 'ready');
      announce('Choose your barangay to complete the address.');
      await loadPostal();
      return;
    }
    setState(barangay, 'complete');
    announce('Address hierarchy complete.', 'complete');
    await loadPostal();
  });

  const initialize = async () => {
    const selected = {
      region: region.dataset.selected || '', province: province.dataset.selected || '',
      locality: locality.dataset.selected || '', barangay: barangay.dataset.selected || '',
    };
    announce('Loading Philippine regions…');
    await load(region, endpoint, 'Choose a region');
    announce('Start by choosing your region.');
    if (!selected.region) return;
    region.value = selected.region;
    setState(region, 'complete');
    await load(province, `${endpoint}?region_code=${encodeURIComponent(selected.region)}`, 'Choose a province / district');
    province.value = selected.province;
    if (!selected.province) return;
    setState(province, 'complete');
    await load(locality, `${endpoint}?province_id=${encodeURIComponent(selected.province)}`, 'Choose a city / municipality');
    locality.value = selected.locality;
    if (!selected.locality) return;
    setState(locality, 'complete');
    await load(barangay, `${endpoint}?locality_id=${encodeURIComponent(selected.locality)}`, 'Choose a barangay');
    barangay.value = selected.barangay;
    if (selected.barangay) setState(barangay, 'complete');
    await loadPostal();
    announce(selected.barangay ? 'Address hierarchy complete.' : 'Finish by choosing your barangay.', selected.barangay ? 'complete' : '');
  };

  reset(province, 'Select a region first');
  reset(locality, 'Select a province first');
  reset(barangay, 'Select a city first');
  resetPostal('Choose your city and barangay to find the postal code.', true);
  initialize().catch(() => handleFailure(region));
});
