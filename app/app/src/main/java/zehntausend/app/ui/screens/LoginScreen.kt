package zehntausend.app.ui.screens
import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import net.kronisoft.zehntausend.R
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
    var aiCount by remember { mutableStateOf(0) }
    var winScore by remember { mutableStateOf(10000) }

    Column(
        modifier = Modifier.fillMaxSize().padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Image(
                painter = painterResource(R.drawable.app_icon),
                contentDescription = null,
                modifier = Modifier.size(40.dp)
            )
            Spacer(modifier = Modifier.width(8.dp))
            Text("Zehntausend", fontSize = 32.sp, fontWeight = FontWeight.Bold)
        }
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
        } else {
            Text("KI-Gegner: $aiCount", style = MaterialTheme.typography.bodyMedium)
            Spacer(modifier = Modifier.height(4.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                listOf(0, 1, 2, 3).forEach { n ->
                    FilterChip(
                        selected = aiCount == n,
                        onClick = { aiCount = n },
                        label = { Text("$n") },
                        modifier = Modifier.weight(1f)
                    )
                }
            }
            Spacer(modifier = Modifier.height(16.dp))
            Text("Zielpunktzahl: $winScore", style = MaterialTheme.typography.bodyMedium)
            Spacer(modifier = Modifier.height(4.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                listOf(5000, 10000, 15000).forEach { n ->
                    FilterChip(
                        selected = winScore == n,
                        onClick = { winScore = n },
                        label = { Text("$n") },
                        modifier = Modifier.weight(1f)
                    )
                }
            }
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
                Button(
                    onClick = {
                        if (playerName.isNotBlank())
                            viewModel.createGame(playerName, aiCount, winScore, onSuccess = onGameCreated)
                    },
                    enabled = playerName.isNotBlank(),
                    modifier = Modifier.fillMaxWidth()
                ) { Text("Neues Spiel erstellen") }
                Spacer(modifier = Modifier.height(8.dp))
                OutlinedButton(
                    onClick = { showJoin = true },
                    modifier = Modifier.fillMaxWidth()
                ) { Text("Spiel beitreten") }
            } else {
                Button(
                    onClick = {
                        if (playerName.isNotBlank() && gameCode.isNotBlank())
                            viewModel.joinGame(gameCode, playerName, onGameJoined)
                    },
                    enabled = playerName.isNotBlank() && gameCode.isNotBlank(),
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