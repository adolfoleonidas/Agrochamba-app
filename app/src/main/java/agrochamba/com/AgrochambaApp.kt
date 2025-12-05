package agrochamba.com

import android.app.Application
import dagger.hilt.android.HiltAndroidApp

@HiltAndroidApp
class AgrochambaApp : Application() {

    override fun onCreate() {
        super.onCreate()
        // Migrado completamente a WordPress - Firebase eliminado
    }
}
