import React from 'react';
import {Img, staticFile} from 'remotion';

type BrandMarkProps = {
  logoUrl: string;
  companyName: string;
  accentColor: string;
  size?: number;
};

export const BrandMark: React.FC<BrandMarkProps> = ({
  logoUrl,
  companyName,
  accentColor,
  size = 72,
}) => {
  const src =
    logoUrl.startsWith('http') || logoUrl.startsWith('/')
      ? logoUrl
      : staticFile(logoUrl);

  return (
    <div
      style={{
        display: 'flex',
        alignItems: 'center',
        gap: 20,
      }}
    >
      <Img
        src={src}
        style={{
          width: size,
          height: size,
          objectFit: 'contain',
          borderRadius: 16,
        }}
      />
      <div
        style={{
          fontSize: size * 0.55,
          fontWeight: 800,
          letterSpacing: '0.04em',
          color: '#f3f6fb',
          fontFamily:
            'Inter, Segoe UI, system-ui, -apple-system, sans-serif',
        }}
      >
        {companyName.split(' ')[0]}
        <span style={{color: accentColor}}>
          {companyName.includes(' ')
            ? ` ${companyName.split(' ').slice(1).join(' ')}`
            : ''}
        </span>
      </div>
    </div>
  );
};
