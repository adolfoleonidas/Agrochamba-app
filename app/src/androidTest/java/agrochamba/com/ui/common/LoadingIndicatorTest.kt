package agrochamba.com.ui.common

import androidx.compose.ui.test.assertIsDisplayed
import androidx.compose.ui.test.junit4.createComposeRule
import androidx.compose.ui.test.onNodeWithContentDescription
import org.junit.Rule
import org.junit.Test

class LoadingIndicatorTest {

    @get:Rule
    val composeTestRule = createComposeRule()

    @Test
    fun loadingIndicator_shouldBeDisplayed() {
        // Given
        composeTestRule.setContent {
            LoadingIndicator()
        }

        // Then - Buscar el CircularProgressIndicator
        composeTestRule.onNodeWithContentDescription("Loading")
            .assertIsDisplayed()
    }

    @Test
    fun smallLoadingIndicator_shouldBeDisplayed() {
        // Given
        composeTestRule.setContent {
            SmallLoadingIndicator()
        }

        // Then - Buscar el CircularProgressIndicator
        composeTestRule.onNodeWithContentDescription("Loading")
            .assertIsDisplayed()
    }
}

