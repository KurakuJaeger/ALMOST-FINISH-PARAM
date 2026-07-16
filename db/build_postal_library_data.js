'use strict';

const fs = require('fs');
const path = require('path');

const sourceFile = process.argv[2] || path.join(__dirname, 'data', 'use-postal-ph.js');
const outputFile = process.argv[3] || path.join(__dirname, 'data', 'philippine_postal_codes.json');
const usePostalPH = require(sourceFile);
const result = usePostalPH().fetchDataLists();

const repairEncoding = (value) => {
  const text = String(value || '').trim().replace(/\s+/g, ' ');
  return /Ã|Â/.test(text) ? Buffer.from(text, 'latin1').toString('utf8') : text;
};

const unique = new Map();
for (const row of result.data || []) {
  const postalCode = String(row.post_code || '').padStart(4, '0');
  if (!/^\d{4}$/.test(postalCode)) continue;
  const item = {
    region: repairEncoding(row.region),
    location: repairEncoding(row.location),
    area: repairEncoding(row.municipality),
    postal_code: postalCode,
  };
  unique.set(`${item.region}|${item.location}|${item.area}|${item.postal_code}`, item);
}

const items = [...unique.values()].sort((a, b) =>
  a.region.localeCompare(b.region) ||
  a.location.localeCompare(b.location) ||
  a.area.localeCompare(b.area) ||
  a.postal_code.localeCompare(b.postal_code)
);

if (items.length < 1000) {
  throw new Error(`Only ${items.length} postal-code rows were found; refusing to replace the dataset.`);
}

const payload = {
  source: 'https://github.com/blckclov3r/use-postal-ph',
  source_version: '1.1.14',
  official_reference: 'https://phlpost.gov.ph/zip-code-locator/',
  count: items.length,
  items,
};

fs.writeFileSync(outputFile, `${JSON.stringify(payload, null, 2)}\n`, 'utf8');
console.log(`Wrote ${items.length} postal-code rows to ${outputFile}.`);
