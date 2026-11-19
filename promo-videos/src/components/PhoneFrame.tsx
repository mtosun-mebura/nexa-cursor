import React from 'react';
import {AbsoluteFill, Img, staticFile} from 'remotion';

type PhoneFrameProps = {
  src: string;
  width?: number;
  accentColor?: string;
};

export const PhoneFrame: React.FC<PhoneFrameProps> = ({
  src,
  width = 360,
  accentColor = '#f97316',
}) => {
  const height = Math.round(width * 2.05);
  const bezel = Math.max(10, Math.round(width * 0.035));
  const radius = Math.round(width * 0.12);

  return (
    <div
      style={{
        width,
        height,
        borderRadius: radius,
        padding: bezel,
        background:
          'linear-gradient(160deg, #1e293b 0%, #0f172a 55%, #020617 100%)',
        boxShadow: `0 28px 80px rgba(0,0,0,0.55), 0 0 0 2px ${accentColor}55`,
        position: 'relative',
      }}
    >
      <div
        style={{
          position: 'absolute',
          top: bezel * 0.55,
          left: '50%',
          transform: 'translateX(-50%)',
          width: width * 0.28,
          height: bezel * 0.55,
          borderRadius: 999,
          background: '#020617',
          zIndex: 2,
        }}
      />
      <div
        style={{
          width: '100%',
          height: '100%',
          borderRadius: radius - bezel,
          overflow: 'hidden',
          background: '#0b1220',
          position: 'relative',
        }}
      >
        <AbsoluteFill>
          <Img
            src={src.startsWith('http') || src.startsWith('/') ? src : staticFile(src)}
            style={{width: '100%', height: '100%', objectFit: 'cover'}}
          />
        </AbsoluteFill>
      </div>
    </div>
  );
};
