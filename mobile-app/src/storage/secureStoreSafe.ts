import * as SecureStore from 'expo-secure-store';

/**
 * Achtergrond/slot: Keychain met WHEN_UNLOCKED weigert read/write
 * (“User interaction is not allowed”). Gebruik AFTER_FIRST_UNLOCK zodat
 * GPS-wake en offer-poll op een vergrendeld scherm niet crashen.
 */
const BG_OPTS: SecureStore.SecureStoreOptions = {
  keychainAccessible: SecureStore.AFTER_FIRST_UNLOCK,
};

export async function secureGet(key: string): Promise<string | null> {
  try {
    return await SecureStore.getItemAsync(key, BG_OPTS);
  } catch {
    try {
      return await SecureStore.getItemAsync(key);
    } catch {
      return null;
    }
  }
}

export async function secureSet(key: string, value: string): Promise<void> {
  try {
    await SecureStore.setItemAsync(key, value, BG_OPTS);
    return;
  } catch {
    /* Legacy WHEN_UNLOCKED-items: update faalt op slot; migreer via delete+add. */
  }
  try {
    await SecureStore.deleteItemAsync(key, BG_OPTS);
  } catch {
    try {
      await SecureStore.deleteItemAsync(key);
    } catch {
      /* locked */
    }
  }
  try {
    await SecureStore.setItemAsync(key, value, BG_OPTS);
  } catch {
    /* slotscherm / geen UI — in-memory blijft leidend tot volgende unlock */
  }
}

export async function secureDelete(key: string): Promise<void> {
  try {
    await SecureStore.deleteItemAsync(key, BG_OPTS);
  } catch {
    try {
      await SecureStore.deleteItemAsync(key);
    } catch {
      /* ignore */
    }
  }
}
