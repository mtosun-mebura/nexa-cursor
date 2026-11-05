#!/usr/bin/env node
/**
 * Genereert ritgeluid-WAVs gelijk aan productie chauffeur-web
 * (backend/public/assets/js/taxi-driver-app.js → playNewRideSound).
 * 44.1 kHz mono PCM16 — vereist voor iOS lock-screen push-geluid.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const SAMPLE_RATE = 44100;
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const outDir = path.join(__dirname, '../assets/sounds');
const iosDir = path.join(__dirname, '../ios/NexaTaxi');

function expRamp(t0, v0, t1, v1, t) {
  const a = Math.max(v0, 1e-4);
  const b = Math.max(v1, 1e-4);
  if (t <= t0) return a;
  if (t >= t1) return b;
  const x = (t - t0) / (t1 - t0);
  return a * Math.pow(b / a, x);
}

function sampleWave(type, phase) {
  const x = ((phase % (Math.PI * 2)) + Math.PI * 2) % (Math.PI * 2);
  if (type === 'triangle') {
    const u = x / (Math.PI * 2);
    if (u < 0.25) return u * 4;
    if (u < 0.75) return 2 - u * 4;
    return u * 4 - 4;
  }
  return Math.sin(x);
}

function renderNotes(notes, { siren } = {}) {
  let end = 0;
  for (const n of notes) {
    end = Math.max(end, (n.delay || 0) + (n.dur || 0.14) + 0.05);
  }
  if (siren) end = Math.max(end, 0.9);
  const nSamples = Math.ceil(end * SAMPLE_RATE) + 1;
  const buf = new Float64Array(nSamples);

  for (const note of notes) {
    const t0 = note.delay || 0;
    const dur = note.dur || 0.14;
    const peak = note.peak || 0.3;
    const type = note.type || 'sine';
    const freq = note.freq;
    const i0 = Math.floor(t0 * SAMPLE_RATE);
    const i1 = Math.min(nSamples - 1, Math.ceil((t0 + dur + 0.02) * SAMPLE_RATE));
    let phase = 0;
    const phaseInc = (2 * Math.PI * freq) / SAMPLE_RATE;
    for (let i = i0; i <= i1; i++) {
      const t = i / SAMPLE_RATE;
      const g =
        t < t0 + 0.025
          ? expRamp(t0, 0.0001, t0 + 0.025, peak, t)
          : expRamp(t0 + 0.025, peak, t0 + dur, 0.0001, t);
      buf[i] += g * sampleWave(type, phase);
      phase += phaseInc;
    }
  }

  if (siren) {
    let phase = 0;
    for (let i = 0; i < nSamples; i++) {
      const t = i / SAMPLE_RATE;
      let freq = 620;
      if (t < 0.28) freq = 620 + (1180 - 620) * (t / 0.28);
      else if (t < 0.56) freq = 1180 + (620 - 1180) * ((t - 0.28) / 0.28);
      else if (t < 0.84) freq = 620 + (1100 - 620) * ((t - 0.56) / 0.28);
      else freq = 1100;
      const g =
        t < 0.05
          ? expRamp(0, 0.0001, 0.05, 0.28, t)
          : t < 0.8
            ? 0.28
            : expRamp(0.8, 0.28, 0.9, 0.0001, t);
      phase += (2 * Math.PI * freq) / SAMPLE_RATE;
      buf[i] += g * sampleWave('triangle', phase);
    }
  }

  let peak = 0;
  for (let i = 0; i < buf.length; i++) peak = Math.max(peak, Math.abs(buf[i]));
  const scale = peak > 0.95 ? 0.95 / peak : 1;
  const pcm = Buffer.alloc(nSamples * 2);
  for (let i = 0; i < nSamples; i++) {
    let v = Math.round(buf[i] * scale * 32767);
    if (v > 32767) v = 32767;
    if (v < -32768) v = -32768;
    pcm.writeInt16LE(v, i * 2);
  }
  return pcm;
}

function writeWav(filePath, pcm) {
  const dataSize = pcm.length;
  const header = Buffer.alloc(44);
  header.write('RIFF', 0);
  header.writeUInt32LE(36 + dataSize, 4);
  header.write('WAVE', 8);
  header.write('fmt ', 12);
  header.writeUInt32LE(16, 16);
  header.writeUInt16LE(1, 20);
  header.writeUInt16LE(1, 22);
  header.writeUInt32LE(SAMPLE_RATE, 24);
  header.writeUInt32LE(SAMPLE_RATE * 2, 28);
  header.writeUInt16LE(2, 32);
  header.writeUInt16LE(16, 34);
  header.write('data', 36);
  header.writeUInt32LE(dataSize, 40);
  fs.writeFileSync(filePath, Buffer.concat([header, pcm]));
}

const tones = {
  chime: [
    { freq: 1047, type: 'sine', delay: 0, dur: 0.28, peak: 0.32 },
    { freq: 1319, type: 'sine', delay: 0.16, dur: 0.3, peak: 0.3 },
    { freq: 1568, type: 'sine', delay: 0.32, dur: 0.38, peak: 0.28 },
  ],
  alert: [
    { freq: 1480, type: 'sine', delay: 0, dur: 0.09, peak: 0.38 },
    { freq: 1480, type: 'sine', delay: 0.13, dur: 0.09, peak: 0.38 },
    { freq: 1480, type: 'sine', delay: 0.26, dur: 0.09, peak: 0.38 },
    { freq: 1760, type: 'sine', delay: 0.39, dur: 0.12, peak: 0.4 },
  ],
  classic: [
    { freq: 880, type: 'sine', delay: 0, dur: 0.14, peak: 0.4 },
    { freq: 1174, type: 'sine', delay: 0.18, dur: 0.14, peak: 0.4 },
  ],
  soft: [
    { freq: 392, type: 'triangle', delay: 0, dur: 0.32, peak: 0.22 },
    { freq: 494, type: 'triangle', delay: 0.22, dur: 0.36, peak: 0.2 },
  ],
};

fs.mkdirSync(outDir, { recursive: true });
for (const [name, notes] of Object.entries(tones)) {
  const pcm = renderNotes(notes);
  const file = path.join(outDir, `${name}.wav`);
  writeWav(file, pcm);
  if (fs.existsSync(iosDir)) fs.copyFileSync(file, path.join(iosDir, `${name}.wav`));
  console.log('wrote', file);
}
{
  const pcm = renderNotes([], { siren: true });
  const file = path.join(outDir, 'siren.wav');
  writeWav(file, pcm);
  if (fs.existsSync(iosDir)) fs.copyFileSync(file, path.join(iosDir, 'siren.wav'));
  console.log('wrote', file);
}
