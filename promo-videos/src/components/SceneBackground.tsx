import React from 'react';
import {AbsoluteFill} from 'remotion';

type SceneBackgroundProps = {
  backgroundColor: string;
  primaryColor: string;
  accentColor: string;
  children?: React.ReactNode;
};

export const SceneBackground: React.FC<SceneBackgroundProps> = ({
  backgroundColor,
  primaryColor,
  accentColor,
  children,
}) => (
  <AbsoluteFill
    style={{
      background: `
        radial-gradient(1200px 600px at 8% -10%, ${primaryColor}40, transparent 55%),
        radial-gradient(900px 500px at 100% 0%, ${accentColor}30, transparent 50%),
        ${backgroundColor}
      `,
      color: '#f3f6fb',
      fontFamily: 'Inter, Segoe UI, system-ui, -apple-system, sans-serif',
    }}
  >
    {children}
  </AbsoluteFill>
);
