/**
 * Bottom padding for tab bars, based on device chrome.
 *
 * - Home-indicator / gesture phones (`insets.bottom` typically ≥ 20):
 *   place the tabs lower, just above the indicator.
 * - Home-button phones (`insets.bottom` ≈ 0):
 *   keep clear padding so tabs sit above the physical home button.
 */
export function tabBarBottomPadding(insetsBottom: number): number {
  if (insetsBottom >= 20) {
    // Face ID / gesture nav: push content down, keep a small cushion above the indicator.
    return Math.max(10, insetsBottom - 14);
  }
  if (insetsBottom > 0) {
    return Math.max(10, insetsBottom);
  }
  // Classic home button / no system inset.
  return 12;
}
