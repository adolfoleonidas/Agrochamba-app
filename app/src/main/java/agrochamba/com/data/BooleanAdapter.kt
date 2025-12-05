package agrochamba.com.data

import com.squareup.moshi.*
import com.squareup.moshi.Types
import java.lang.reflect.Type

/**
 * Adaptador personalizado para manejar Boolean que puede venir como string, número o boolean
 */
class BooleanAdapter {
    @FromJson
    fun fromJson(reader: JsonReader): Boolean? {
        return when (reader.peek()) {
            JsonReader.Token.BOOLEAN -> {
                reader.nextBoolean()
            }
            JsonReader.Token.STRING -> {
                val stringValue = reader.nextString()
                when (stringValue.lowercase()) {
                    "true", "1", "yes", "on" -> true
                    "false", "0", "no", "off", "" -> false
                    else -> null
                }
            }
            JsonReader.Token.NUMBER -> {
                val numberValue = reader.nextInt()
                numberValue != 0
            }
            JsonReader.Token.NULL -> {
                reader.skipValue()
                null
            }
            else -> {
                reader.skipValue()
                null
            }
        }
    }

    @ToJson
    fun toJson(writer: JsonWriter, value: Boolean?) {
        if (value == null) {
            writer.nullValue()
        } else {
            writer.value(value)
        }
    }
}

/**
 * Factory para registrar el adaptador de Boolean
 */
class BooleanAdapterFactory : JsonAdapter.Factory {
    override fun create(type: Type, annotations: MutableSet<out Annotation>, moshi: Moshi): JsonAdapter<*>? {
        if (Types.getRawType(type) == Boolean::class.java) {
            val adapter = BooleanAdapter()
            return object : JsonAdapter<Boolean>() {
                override fun fromJson(reader: JsonReader): Boolean? = adapter.fromJson(reader)
                override fun toJson(writer: JsonWriter, value: Boolean?) = adapter.toJson(writer, value)
            }
        }
        return null
    }
}

