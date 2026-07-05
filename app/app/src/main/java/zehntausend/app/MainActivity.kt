package zehntausend.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.lifecycle.viewmodel.compose.viewModel
import zehntausend.app.ui.screens.*
import zehntausend.app.ui.theme.ZehntausendTheme
import zehntausend.app.viewmodel.GameViewModel

enum class Screen { LOGIN, LOBBY, GAME, RESULT }

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            ZehntausendTheme {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background
                ) {
                    val viewModel: GameViewModel = viewModel()
                    var screen by remember { mutableStateOf(Screen.LOGIN) }

                    when (screen) {
                        Screen.LOGIN -> LoginScreen(
                            viewModel = viewModel,
                            onGameCreated = { screen = Screen.LOBBY },
                            onGameJoined = { screen = Screen.LOBBY }
                        )
                        Screen.LOBBY -> LobbyScreen(
                            viewModel = viewModel,
                            onGameStarted = { screen = Screen.GAME }
                        )
                        Screen.GAME -> GameScreen(
                            viewModel = viewModel,
                            onGameOver = { screen = Screen.RESULT }
                        )
                        Screen.RESULT -> ResultScreen(
                            viewModel = viewModel,
                            onPlayAgain = { screen = Screen.LOGIN }
                        )
                    }
                }
            }
        }
    }
}

