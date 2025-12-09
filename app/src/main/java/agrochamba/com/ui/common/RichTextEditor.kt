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
    // Usar una clave estable para remember, no el valor que cambia constantemente
    var textFieldValue by remember { mutableStateOf(TextFieldValue(value, TextRange(value.length))) }
    var isInternalChange by remember { mutableStateOf(false) }
    
    // Actualizar cuando cambia el valor externo (solo cuando viene de fuera, no de nuestra edición)
    LaunchedEffect(value) {
        // Solo actualizar si el cambio NO viene de nuestra edición interna
        if (!isInternalChange && textFieldValue.text != value) {
            // Preservar la posición del cursor si es posible
            val currentCursor = textFieldValue.selection.start
            val newCursor = currentCursor.coerceIn(0, value.length)
            textFieldValue = TextFieldValue(value, TextRange(newCursor))
        }
        // Resetear la bandera después de procesar
        isInternalChange = false
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
                    // Marcar que este es un cambio interno
                    isInternalChange = true
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
    if (text.isEmpty()) return false
    
    try {
        if (selection.collapsed) {
            // Si no hay selección, verificar el formato en la posición del cursor
            val safeStart = selection.start.coerceIn(0, text.length)
            // Asegurar que tenemos suficiente espacio para buscar el marcador
            if (safeStart < 0 || safeStart > text.length) return false
            
            val start = maxOf(0, safeStart - marker.length).coerceIn(0, text.length)
            val end = minOf(text.length, safeStart + marker.length).coerceIn(0, text.length)
            
            // Validación más robusta: asegurar que start < end y ambos están en rango válido
            if (start < 0 || end > text.length || start >= end || start == end) return false
            
            // Validación final antes de substring
            if (start >= text.length || end > text.length || start < 0 || end < 0) return false
            
            val context = text.substring(start, end)
            return context.contains(marker)
        } else {
            // Si hay selección, verificar si está envuelta en el marcador
            val start = selection.start.coerceIn(0, text.length)
            val end = selection.end.coerceIn(0, text.length)
            
            // Validación más robusta: asegurar que start < end y ambos están en rango válido
            if (start < 0 || end > text.length || start >= end || start == end) return false
            
            // Validación final antes de substring
            if (start >= text.length || end > text.length || start < 0 || end < 0) return false
            
            val selectedText = text.substring(start, end)
            return selectedText.startsWith(marker) && selectedText.endsWith(marker)
        }
    } catch (e: StringIndexOutOfBoundsException) {
        // Si ocurre un error de índice, simplemente retornar false
        return false
    }
}

/**
 * Detecta si la línea actual es una lista con viñetas
 */
private fun detectBulletList(text: String, selection: TextRange): Boolean {
    if (text.isEmpty()) return false
    try {
        val safeStart = selection.start.coerceIn(0, text.length)
        val searchStart = maxOf(0, safeStart - 1)
        val lineStart = text.lastIndexOf('\n', searchStart) + 1
        val lineEnd = text.indexOf('\n', safeStart).let { if (it == -1) text.length else it }
        // Validación más robusta
        if (lineStart < 0 || lineEnd > text.length || lineStart >= lineEnd || lineStart == lineEnd) return false
        // Validación final antes de substring
        if (lineStart >= text.length || lineEnd > text.length || lineStart < 0 || lineEnd < 0) return false
        val line = text.substring(lineStart, lineEnd)
        return line.trimStart().startsWith("- ") || line.trimStart().startsWith("* ")
    } catch (e: StringIndexOutOfBoundsException) {
        return false
    }
}

/**
 * Detecta si la línea actual es una lista numerada
 */
private fun detectNumberedList(text: String, selection: TextRange): Boolean {
    if (text.isEmpty()) return false
    try {
        val safeStart = selection.start.coerceIn(0, text.length)
        val searchStart = maxOf(0, safeStart - 1)
        val lineStart = text.lastIndexOf('\n', searchStart) + 1
        val lineEnd = text.indexOf('\n', safeStart).let { if (it == -1) text.length else it }
        // Validación más robusta
        if (lineStart < 0 || lineEnd > text.length || lineStart >= lineEnd || lineStart == lineEnd) return false
        // Validación final antes de substring
        if (lineStart >= text.length || lineEnd > text.length || lineStart < 0 || lineEnd < 0) return false
        val line = text.substring(lineStart, lineEnd)
        return Regex("^\\d+\\.\\s").containsMatchIn(line.trimStart())
    } catch (e: StringIndexOutOfBoundsException) {
        return false
    }
}

/**
 * Alterna el formato de negrita o cursiva en la selección
 */
private fun toggleFormat(textFieldValue: TextFieldValue, marker: String): TextFieldValue {
    val text = textFieldValue.text
    val selection = textFieldValue.selection
    
    try {
        return if (selection.collapsed) {
            // Insertar marcadores en la posición del cursor
            val safeStart = selection.start.coerceIn(0, text.length)
            val newText = text.substring(0, safeStart) + marker + marker + text.substring(safeStart)
            val newSelection = TextRange(safeStart + marker.length)
            textFieldValue.copy(text = newText, selection = newSelection)
        } else {
            // Envolver la selección con marcadores
            val start = selection.start.coerceIn(0, text.length)
            val end = selection.end.coerceIn(0, text.length)
            if (start >= end || start < 0 || end > text.length) return textFieldValue
            
            val selectedText = text.substring(start, end)
            val isFormatted = selectedText.startsWith(marker) && selectedText.endsWith(marker)
            
            if (isFormatted) {
                // Remover formato
                val unformatted = selectedText.removePrefix(marker).removeSuffix(marker)
                val newText = text.substring(0, start) + unformatted + text.substring(end)
                val newSelection = TextRange(start, start + unformatted.length)
                textFieldValue.copy(text = newText, selection = newSelection)
            } else {
                // Aplicar formato
                val formatted = marker + selectedText + marker
                val newText = text.substring(0, start) + formatted + text.substring(end)
                val newSelection = TextRange(start, start + formatted.length)
                textFieldValue.copy(text = newText, selection = newSelection)
            }
        }
    } catch (e: StringIndexOutOfBoundsException) {
        // Si ocurre un error, retornar el valor original
        return textFieldValue
    }
}

/**
 * Alterna lista con viñetas en la línea actual
 */
private fun toggleBulletList(textFieldValue: TextFieldValue): TextFieldValue {
    val text = textFieldValue.text
    val selection = textFieldValue.selection
    
    try {
        val safeStart = selection.start.coerceIn(0, text.length)
        val searchStart = maxOf(0, safeStart - 1)
        val lineStart = text.lastIndexOf('\n', searchStart) + 1
        val lineEnd = text.indexOf('\n', safeStart).let { if (it == -1) text.length else it }
        
        if (lineStart < 0 || lineEnd > text.length || lineStart >= lineEnd) return textFieldValue
        
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
    } catch (e: StringIndexOutOfBoundsException) {
        return textFieldValue
    }
}

/**
 * Alterna lista numerada en la línea actual
 */
private fun toggleNumberedList(textFieldValue: TextFieldValue): TextFieldValue {
    val text = textFieldValue.text
    val selection = textFieldValue.selection
    
    try {
        val safeStart = selection.start.coerceIn(0, text.length)
        val searchStart = maxOf(0, safeStart - 1)
        val lineStart = text.lastIndexOf('\n', searchStart) + 1
        val lineEnd = text.indexOf('\n', safeStart).let { if (it == -1) text.length else it }
        
        if (lineStart < 0 || lineEnd > text.length || lineStart >= lineEnd) return textFieldValue
        
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
    } catch (e: StringIndexOutOfBoundsException) {
        return textFieldValue
    }
}

