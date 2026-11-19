import React from 'react';
import {
  AbsoluteFill,
  Img,
  interpolate,
  spring,
  staticFile,
  useCurrentFrame,
  useVideoConfig,
} from 'remotion';
import {PhoneFrame} from './PhoneFrame';

type FeatureSlideProps = {
  title: string;
  body: string;
  image: string;
  primaryColor: string;
  accentColor: string;
  backgroundColor: string;
  index: number;
  usePhone?: boolean;
};

export const FeatureSlide: React.FC<FeatureSlideProps> = ({
  title,
  body,
  image,
  primaryColor,
  accentColor,
  backgroundColor,
  index,
  usePhone = false,
}) => {
  const frame = useCurrentFrame();
  const {fps} = useVideoConfig();

  const enter = spring({
    frame,
    fps,
    config: {damping: 18, stiffness: 120},
  });
  const opacity = interpolate(enter, [0, 1], [0, 1]);
  const x = interpolate(enter, [0, 1], [80, 0]);
  const imgScale = interpolate(enter, [0, 1], [0.92, 1]);

  const flip = index % 2 === 1;
  const src =
    image.startsWith('http') || image.startsWith('/')
      ? image
      : staticFile(image);

  return (
    <AbsoluteFill
      style={{
        background: `radial-gradient(1000px 500px at ${flip ? '90%' : '10%'} 0%, ${primaryColor}33, transparent 55%), ${backgroundColor}`,
        color: '#f3f6fb',
        fontFamily: 'Inter, Segoe UI, system-ui, sans-serif',
        padding: 96,
        display: 'flex',
        flexDirection: flip ? 'row-reverse' : 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        gap: 64,
        opacity,
      }}
    >
      <div style={{flex: 1, transform: `translateX(${flip ? -x : x}px)`}}>
        <div
          style={{
            display: 'inline-block',
            padding: '10px 18px',
            borderRadius: 999,
            background: `${accentColor}22`,
            color: accentColor,
            fontWeight: 700,
            fontSize: 22,
            marginBottom: 28,
          }}
        >
          Feature {index + 1}
        </div>
        <h2
          style={{
            fontSize: 72,
            lineHeight: 1.1,
            margin: '0 0 24px',
            fontWeight: 800,
          }}
        >
          {title}
        </h2>
        <p
          style={{
            fontSize: 34,
            lineHeight: 1.4,
            margin: 0,
            color: '#9aa8bd',
            maxWidth: 640,
          }}
        >
          {body}
        </p>
      </div>
      <div
        style={{
          flex: 1,
          display: 'flex',
          justifyContent: 'center',
          transform: `scale(${imgScale})`,
        }}
      >
        {usePhone ? (
          <PhoneFrame src={image} width={340} accentColor={accentColor} />
        ) : (
          <Img
            src={src}
            style={{
              width: '100%',
              maxWidth: 780,
              borderRadius: 28,
              boxShadow: '0 30px 80px rgba(0,0,0,0.45)',
              objectFit: 'cover',
            }}
          />
        )}
      </div>
    </AbsoluteFill>
  );
};
