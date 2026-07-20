import { deflateSync } from 'zlib';
import { writeFileSync, mkdirSync } from 'fs';

const W = 512, H = 512;

function makeNavyPng(w, h) {
    const sig = Buffer.from([137,80,78,71,13,10,26,10]);

    const crcTable = new Uint32Array(256);
    for (let n = 0; n < 256; n++) {
        let c = n;
        for (let k = 0; k < 8; k++) c = (c & 1) ? 0xEDB88320 ^ (c >>> 1) : c >>> 1;
        crcTable[n] = c;
    }
    function crc(buf) {
        let c = 0xFFFFFFFF;
        for (let i = 0; i < buf.length; i++) c = crcTable[(c ^ buf[i]) & 0xFF] ^ (c >>> 8);
        return (c ^ 0xFFFFFFFF) >>> 0;
    }
    function chunk(type, data) {
        const t = Buffer.from(type);
        const len = Buffer.alloc(4); len.writeUInt32BE(data.length);
        const cc = Buffer.alloc(4); cc.writeUInt32BE(crc(Buffer.concat([t,data])));
        return Buffer.concat([len, t, data, cc]);
    }

    const ihdr = Buffer.alloc(13);
    ihdr.writeUInt32BE(w, 0); ihdr.writeUInt32BE(h, 4);
    ihdr[8]=8; ihdr[9]=2;

    const rows = [];
    for (let y = 0; y < h; y++) {
        const row = Buffer.alloc(1 + w*3);
        row[0] = 0;
        for (let x = 0; x < w; x++) {
            const o = 1 + x*3;
            // Navy background
            row[o]=26; row[o+1]=39; row[o+2]=68;
        }
        rows.push(row);
    }

    const raw = deflateSync(Buffer.concat(rows));
    return Buffer.concat([sig, chunk('IHDR',ihdr), chunk('IDAT',raw), chunk('IEND',Buffer.alloc(0))]);
}

try { mkdirSync('src-tauri/icons', { recursive: true }); } catch(_) {}

const png = makeNavyPng(W, H);
writeFileSync('app-icon.png', png);
console.log('app-icon.png created (' + png.length + ' bytes)');
