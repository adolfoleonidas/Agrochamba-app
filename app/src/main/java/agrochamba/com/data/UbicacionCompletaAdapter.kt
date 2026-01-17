package agrochamba.com.data

import com.squareup.moshi.*
import java.lang.reflect.Type

/**
 * Adaptador personalizado para manejar UbicacionCompleta que puede venir como:
 * - Un objeto válido: {"departamento": "Ica", "provincia": "Ica", "distrito": "Subtanjalla", ...}
 * - Un array vacío: [] (WordPress devuelve esto cuando no hay datos)
 * - null
 */
class UbicacionCompletaAdapter {
    @FromJson
    fun fromJson(reader: JsonReader): UbicacionCompleta? {
        return when (reader.peek()) {
            JsonReader.Token.BEGIN_OBJECT -> {
                // Es un objeto válido, parsearlo manualmente
                reader.beginObject()
                var departamento = ""
                var provincia = ""
                var distrito = ""
                var direccion: String? = null
                var lat: Double? = null
                var lng: Double? = null
                
                while (reader.hasNext()) {
                    when (reader.nextName()) {
                        "departamento" -> departamento = reader.nextStringOrNull() ?: ""
                        "provincia" -> provincia = reader.nextStringOrNull() ?: ""
                        "distrito" -> distrito = reader.nextStringOrNull() ?: ""
                        "direccion" -> direccion = reader.nextStringOrNull()
                        "lat" -> lat = reader.nextDoubleOrNull()
                        "lng" -> lng = reader.nextDoubleOrNull()
                        "latitud" -> lat = reader.nextDoubleOrNull()
                        "longitud" -> lng = reader.nextDoubleOrNull()
                        else -> reader.skipValue()
                    }
                }
                reader.endObject()
                
                // Solo devolver objeto si tiene al menos departamento
                if (departamento.isNotBlank()) {
                    UbicacionCompleta(
                        departamento = departamento,
                        provincia = provincia.ifBlank { departamento },
                        distrito = distrito.ifBlank { provincia.ifBlank { departamento } },
                        direccion = direccion,
                        lat = lat,
                        lng = lng
                    )
                } else {
                    null
                }
            }
            JsonReader.Token.BEGIN_ARRAY -> {
                // Es un array (probablemente vacío), ignorarlo
                reader.beginArray()
                while (reader.hasNext()) {
                    reader.skipValue()
                }
                reader.endArray()
                null
            }
            JsonReader.Token.NULL -> {
                reader.nextNull<Unit>()
                null
            }
            else -> {
                reader.skipValue()
                null
            }
        }
    }
    
    @ToJson
    fun toJson(writer: JsonWriter, value: UbicacionCompleta?) {
        if (value == null) {
            writer.nullValue()
        } else {
            writer.beginObject()
            writer.name("departamento").value(value.departamento)
            writer.name("provincia").value(value.provincia)
            writer.name("distrito").value(value.distrito)
            value.direccion?.let { writer.name("direccion").value(it) }
            value.lat?.let { writer.name("lat").value(it) }
            value.lng?.let { writer.name("lng").value(it) }
            writer.endObject()
        }
    }
    
    private fun JsonReader.nextStringOrNull(): String? {
        return when (peek()) {
            JsonReader.Token.STRING -> nextString()
            JsonReader.Token.NULL -> {
                nextNull<Unit>()
                null
            }
            else -> {
                skipValue()
                null
            }
        }
    }
    
    private fun JsonReader.nextDoubleOrNull(): Double? {
        return when (peek()) {
            JsonReader.Token.NUMBER -> nextDouble()
            JsonReader.Token.STRING -> {
                val str = nextString()
                str.toDoubleOrNull()
            }
            JsonReader.Token.NULL -> {
                nextNull<Unit>()
                null
            }
            else -> {
                skipValue()
                null
            }
        }
    }
}

/**
 * Factory para registrar el adaptador de UbicacionCompleta
 */
class UbicacionCompletaAdapterFactory : JsonAdapter.Factory {
    override fun create(type: Type, annotations: MutableSet<out Annotation>, moshi: Moshi): JsonAdapter<*>? {
        if (Types.getRawType(type) == UbicacionCompleta::class.java) {
            val adapter = UbicacionCompletaAdapter()
            return object : JsonAdapter<UbicacionCompleta>() {
                override fun fromJson(reader: JsonReader): UbicacionCompleta? = adapter.fromJson(reader)
                override fun toJson(writer: JsonWriter, value: UbicacionCompleta?) = adapter.toJson(writer, value)
            }
        }
        return null
    }
}
