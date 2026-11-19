import React from 'react';
import {createRoot} from 'react-dom/client';
import {PlayerApp} from './PlayerApp';

declare global {
  interface Window {
    remotion_staticBase?: string;
  }
}

// Remotion staticFile() must resolve under the Vite base path.
window.remotion_staticBase = (import.meta.env.BASE_URL || '/promo-videos-player/').replace(
  /\/$/,
  '',
);

const root = document.getElementById('root');
if (root) {
  createRoot(root).render(
    <React.StrictMode>
      <PlayerApp />
    </React.StrictMode>,
  );
}
