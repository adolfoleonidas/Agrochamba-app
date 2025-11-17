package agrochamba.com.data

/**
 * Un objeto singleton para pasar datos complejos entre pantallas sin tener que serializarlos.
 * Útil para datos como JobPost, que son difíciles de pasar como argumentos de navegación.
 */
object AppDataHolder {
    // Contenedor temporal para el trabajo que se quiere ver en detalle
    var selectedJob: JobPost? = null
}
