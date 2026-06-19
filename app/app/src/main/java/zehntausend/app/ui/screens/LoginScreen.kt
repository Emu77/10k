package zehntausend.app.ui.screens

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import zehntausend.app.viewmodel.GameViewModel

@Composable
fun LoginScreen(
    viewModel: GameViewModel,
    onGameCreated: () -> Unit,
    onGameJoined: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    var playerName by remember { mutableStateOf("") }
    var gameCode by remember { mutableStateOf("") }
    var showJoin by remember { mutableStateOf(false) }
    var aiCount by remember { mutableStateOf(1) }

    Column(
        modifier = Modifier.fillMaxSize().padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Text("🎲 Zehntausend", fontSize = 32.sp, fontWeight = FontWeight.Bold)
        Spacer(modifier = Modifier.height(8.dp))
        Text("Multiplayer-Würfelspiel", style = MaterialTheme.typography.bodyMedium)
        Spacer(modifier = Modifier.height(40.dp))

        OutlinedTextField(
            value = playerName,
            onValueChange = { playerName = it },
            label = { Text("Dein Name") },
            modifier = Modifier.fillMaxWidth()
        )
        Spacer(modifier = Modifier.height(16.dp))

        if (showJoin) {
            OutlinedTextField(
                value = gameCode,
                onValueChange = { gameCode = it },
                label = { Text("Spielcode") },
                modifier = Modifier.fillMaxWidth()
            )
            Spacer(modifier = Modifier.height(16.dp))
        }

        uiState.error?.let {
            Text(it, color = MaterialTheme.colorScheme.error)
            Spacer(modifier = Modifier.height(8.dp))
        }

        if (uiState.isLoading) {
            CircularProgressIndicator()
        } else {
            if (!showJoin) {
                Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.fillMaxWidth()) {
                    Checkbox(checked = aiCount > 0, onCheckedChange = { aiCount = if (it) 1 else 0 })
                    Text("KI-Gegner hinzufügen")
                }
                Spacer(modifier = Modifier.height(8.dp))
                Button(
                    onClick = { if (playerName.isNotBlank()) viewModel.createGame(playerName, aiCount, onGameCreated) },
                    modifier = Modifier.fillMaxWidth()
                ) { Text("Neues Spiel erstellen") }
                Spacer(modifier = Modifier.height(8.dp))
                OutlinedButton(
                    onClick = { showJoin = true },
                    modifier = Modifier.fillMaxWidth()
                ) { Text("Spiel beitreten") }
            } else {
                Button(
                    onClick = { if (playerName.isNotBlank() && gameCode.isNotBlank()) viewModel.joinGame(gameCode, playerName, onGameJoined) },
                    modifier = Modifier.fillMaxWidth()
                ) { Text("Beitreten") }
                Spacer(modifier = Modifier.height(8.dp))
                OutlinedButton(
                    onClick = { showJoin = false },
                    modifier = Modifier.fillMaxWidth()
                ) { Text("Zurück") }
            }
        }
    }
}
