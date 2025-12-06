package agrochamba.com.ui.common

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.BasicTextField
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.FormatBold
import androidx.compose.material.icons.filled.FormatItalic
import androidx.compose.material.icons.filled.FormatListBulleted
import androidx.compose.material.icons.filled.FormatListNumbered
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.SolidColor
import androidx.compose.ui.platform.LocalSoftwareKeyboardController
import androidx.compose.ui.text.TextRange
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.TextFieldValue
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

/**
 * Editor de texto rico nativo con formato básico (negrita, cursiva, listas)
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun RichTextEditor(
    value: String,
    onValueChange: (String) -> Unit,
    modifier: Modifier = Modifier,
    placeholder: String = "",
    maxLines: Int = 15,
    enabled: Boolean = true
) {
    val keyboardController = LocalSoftwareKeyboardController.current
    var textFieldValue by remember(value) { mutableStateOf(TextFieldValue(value, TextRange(value.length))) }
    
    // Actualizar cuando cambia el valor externo
    LaunchedEffect(value) {
        if (textFieldValue.text != value) {
            textFieldValue = TextFieldValue(value, TextRange(value.length))
        }
    }
    
    // Detectar formato en la selección actual
    val selection = textFieldValue.selection
    val hasSelection = !selection.collapsed
    val isBold = detectFormat(textFieldValue.text, selection, "**")
    val isItalic = detectFormat(textFieldValue.text, selection, "*")
    val isBulletList = detectBulletList(textFieldValue.text, selection)
    val isNumberedList = detectNumberedList(textFieldValue.text, selection)
    
    Column(
        modifier = modifier
            .fillMaxWidth()
            .background(
                MaterialTheme.colorScheme.surface,
                RoundedCornerShape(12.dp)
            )
            .border(
                1.dp,
                MaterialTheme.colorScheme.outline.copy(alpha = 0.3f),
                RoundedCornerShape(12.dp)
            )
    ) {
        // Campo de texto
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .height((maxLines * 24).dp)
        ) {
            BasicTextField(
                value = textFieldValue,
                onValueChange = { newValue ->
                    textFieldValue = newValue
                    onValueChange(newValue.text)
                },
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp)
                    .verticalScroll(rememberScrollState()),
                textStyle = TextStyle(
                    fontSize = 16.sp,
                    color = MaterialTheme.colorScheme.onSurface,
                    lineHeight = 24.sp
                ),
                cursorBrush = SolidColor(MaterialTheme.colorScheme.primary),
                enabled = enabled,
                keyboardOptions = KeyboardOptions.Default,
                keyboardActions = KeyboardActions(
                    onDone = { keyboardController?.hide() }
                ),
                maxLines = maxLines
            )
            
            // Placeholder
            if (textFieldValue.text.isEmpty() && placeholder.isNotEmpty()) {
                Text(
                    text = placeholder,
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.6f),
                    modifier = Modifier.padding(16.dp)
                )
            }
        }
        
        // Barra de herramientas siempre debajo, pero con estado según selección
        Divider(color = MaterialTheme.colorScheme.outline.copy(alpha = 0.3f))
        FormatToolbar(
            isBold = if (hasSelection) isBold else false,
            isItalic = if (hasSelection) isItalic else false,
            isBulletList = isBulletList,
            isNumberedList = isNumberedList,
            onBoldClick = {
                textFieldValue = toggleFormat(textFieldValue, "**")
                keyboardController?.show()
            },
            onItalicClick = {
                textFieldValue = toggleFormat(textFieldValue, "*")
                keyboardController?.show()
            },
            onBulletListClick = {
                textFieldValue = toggleBulletList(textFieldValue)
                keyboardController?.show()
            },
            onNumberedListClick = {
                textFieldValue = toggleNumberedList(textFieldValue)
                keyboardController?.show()
            }
        )
    }
}

@Composable
private fun FormatToolbar(
    isBold: Boolean,
    isItalic: Boolean,
    isBulletList: Boolean,
    isNumberedList: Boolean,
    onBoldClick: () -> Unit,
    onItalicClick: () -> Unit,
    onBulletListClick: () -> Unit,
    onNumberedListClick: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 8.dp, vertical = 4.dp),
        horizontalArrangement = Arrangement.spacedBy(4.dp)
    ) {
        FormatButton(
            icon = Icons.Default.FormatBold,
            isSelected = isBold,
            onClick = onBoldClick,
            contentDescription = "Negrita"
        )
        
        FormatButton(
            icon = Icons.Default.FormatItalic,
            isSelected = isItalic,
            onClick = onItalicClick,
            contentDescription = "Cursiva"
        )
        
        Spacer(modifier = Modifier.width(8.dp))
        
        FormatButton(
            icon = Icons.Default.FormatListBulleted,
            isSelected = isBulletList,
            onClick = onBulletListClick,
            contentDescription = "Lista con viñetas"
        )
        
        FormatButton(
            icon = Icons.Default.FormatListNumbered,
            isSelected = isNumberedList,
            onClick = onNumberedListClick,
            contentDescription = "Lista numerada"
        )
    }
}

@Composable
private fun FormatButton(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    isSelected: Boolean,
    onClick: () -> Unit,
    contentDescription: String
) {
    IconButton(
        onClick = onClick,
        modifier = Modifier
            .size(36.dp)
            .then(
                if (isSelected) {
                    Modifier.background(
                        MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.5f),
                        RoundedCornerShape(8.dp)
                    )
                } else {
                    Modifier
                }
            )
    ) {
        Icon(
            imageVector = icon,
            contentDescription = contentDescription,
            tint = if (isSelected) {
                MaterialTheme.colorScheme.primary
            } else {
                MaterialTheme.colorScheme.onSurfaceVariant
            },
            modifier = Modifier.size(20.dp)
        )
    }
}

/**
 * Detecta si el texto seleccionado tiene un formato específico
 */
private fun detectFormat(text: String, selection: TextRange, marker: String): Boolean {
    if (selection.collapsed) {
        // Si no hay selección, verificar el formato en la posición del cursor
        val start = maxOf(0, selection.start - marker.length)
        val end = minOf(text.length, selection.start + marker.length)
        val context = text.substring(start, end)
        return context.contains(marker)
    } else {
        // Si hay selección, verificar si está envuelta en el marcador
        val selectedText = text.substring(selection.start, selection.end)
        return selectedText.startsWith(marker) && selectedText.endsWith(marker)
    }
}

/**
 * Detecta si la línea actual es una lista con viñetas
 */
private fun detectBulletList(text: String, selection: TextRange): Boolean {
    val lineStart = text.lastIndexOf('\n', selection.start - 1) + 1
    val lineEnd = text.indexOf('\n', selection.start).let { if (it == -1) text.length else it }
    val line = text.substring(lineStart, lineEnd)
    return line.trimStart().startsWith("- ") || line.trimStart().startsWith("* ")
}

/**
 * Detecta si la línea actual es una lista numerada
 */
private fun detectNumberedList(text: String, selection: TextRange): Boolean {
    val lineStart = text.lastIndexOf('\n', selection.start - 1) + 1
    val lineEnd = text.indexOf('\n', selection.start).let { if (it == -1) text.length else it }
    val line = text.substring(lineStart, lineEnd)
    return Regex("^\\d+\\.\\s").containsMatchIn(line.trimStart())
}

/**
 * Alterna el formato de negrita o cursiva en la selección
 */
private fun toggleFormat(textFieldValue: TextFieldValue, marker: String): TextFieldValue {
    val text = textFieldValue.text
    val selection = textFieldValue.selection
    
    return if (selection.collapsed) {
        // Insertar marcadores en la posición del cursor
        val newText = text.substring(0, selection.start) + marker + marker + text.substring(selection.start)
        val newSelection = TextRange(selection.start + marker.length)
        textFieldValue.copy(text = newText, selection = newSelection)
    } else {
        // Envolver la selección con marcadores
        val selectedText = text.substring(selection.start, selection.end)
        val isFormatted = selectedText.startsWith(marker) && selectedText.endsWith(marker)
        
        if (isFormatted) {
            // Remover formato
            val unformatted = selectedText.removePrefix(marker).removeSuffix(marker)
            val newText = text.substring(0, selection.start) + unformatted + text.substring(selection.end)
            val newSelection = TextRange(selection.start, selection.start + unformatted.length)
            textFieldValue.copy(text = newText, selection = newSelection)
        } else {
            // Aplicar formato
            val formatted = marker + selectedText + marker
            val newText = text.substring(0, selection.start) + formatted + text.substring(selection.end)
            val newSelection = TextRange(selection.start, selection.start + formatted.length)
            textFieldValue.copy(text = newText, selection = newSelection)
        }
    }
}

/**
 * Alterna lista con viñetas en la línea actual
 */
private fun toggleBulletList(textFieldValue: TextFieldValue): TextFieldValue {
    val text = textFieldValue.text
    val selection = textFieldValue.selection
    
    val lineStart = text.lastIndexOf('\n', selection.start - 1) + 1
    val lineEnd = text.indexOf('\n', selection.start).let { if (it == -1) text.length else it }
    val line = text.substring(lineStart, lineEnd)
    val isBulletList = line.trimStart().startsWith("- ") || line.trimStart().startsWith("* ")
    
    return if (isBulletList) {
        // Remover viñeta
        val newLine = line.replaceFirst(Regex("^\\s*[-*]\\s+"), "")
        val newText = text.substring(0, lineStart) + newLine + text.substring(lineEnd)
        val offset = line.length - newLine.length
        val newSelection = TextRange(maxOf(0, selection.start - offset))
        textFieldValue.copy(text = newText, selection = newSelection)
    } else {
        // Agregar viñeta
        val indent = line.takeWhile { it == ' ' }
        val newLine = indent + "- " + line.trimStart()
        val newText = text.substring(0, lineStart) + newLine + text.substring(lineEnd)
        val offset = newLine.length - line.length
        val newSelection = TextRange(selection.start + offset)
        textFieldValue.copy(text = newText, selection = newSelection)
    }
}

/**
 * Alterna lista numerada en la línea actual
 */
private fun toggleNumberedList(textFieldValue: TextFieldValue): TextFieldValue {
    val text = textFieldValue.text
    val selection = textFieldValue.selection
    
    val lineStart = text.lastIndexOf('\n', selection.start - 1) + 1
    val lineEnd = text.indexOf('\n', selection.start).let { if (it == -1) text.length else it }
    val line = text.substring(lineStart, lineEnd)
    val isNumberedList = Regex("^\\d+\\.\\s").containsMatchIn(line.trimStart())
    
    return if (isNumberedList) {
        // Remover numeración
        val newLine = line.replaceFirst(Regex("^\\s*\\d+\\.\\s+"), "")
        val newText = text.substring(0, lineStart) + newLine + text.substring(lineEnd)
        val offset = line.length - newLine.length
        val newSelection = TextRange(maxOf(0, selection.start - offset))
        textFieldValue.copy(text = newText, selection = newSelection)
    } else {
        // Agregar numeración (encontrar el siguiente número)
        val indent = line.takeWhile { it == ' ' }
        val previousLines = text.substring(0, lineStart).split('\n')
        var nextNumber = 1
        for (i in previousLines.size - 2 downTo 0) {
            val prevLine = previousLines[i]
            val match = Regex("^\\s*(\\d+)\\.\\s").find(prevLine.trimStart())
            if (match != null) {
                nextNumber = match.groupValues[1].toIntOrNull()?.plus(1) ?: 1
                break
            }
        }
        val newLine = indent + "$nextNumber. " + line.trimStart()
        val newText = text.substring(0, lineStart) + newLine + text.substring(lineEnd)
        val offset = newLine.length - line.length
        val newSelection = TextRange(selection.start + offset)
        textFieldValue.copy(text = newText, selection = newSelection)
    }
}

