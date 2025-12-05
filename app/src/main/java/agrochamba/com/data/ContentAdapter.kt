package agrochamba.com.data

import com.squareup.moshi.*
import com.squareup.moshi.Types
import java.lang.reflect.Type

/**
 * Adaptador personalizado para manejar Content que puede venir como string o como objeto
 */
class ContentAdapter {
    @FromJson
    fun fromJson(reader: JsonReader): Content? {
        return when (reader.peek()) {
            JsonReader.Token.STRING -> {
                // Si viene como string, crear un objeto Content con ese string
                val stringValue = reader.nextString()
                Content(rendered = stringValue)
            }
            JsonReader.Token.BEGIN_OBJECT -> {
                // Si viene como objeto, parsearlo normalmente
                reader.beginObject()
                var rendered: String? = null
                while (reader.hasNext()) {
                    when (reader.nextName()) {
                        "rendered" -> rendered = reader.nextString()
                        else -> reader.skipValue()
                    }
                }
                reader.endObject()
                Content(rendered = rendered)
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
    fun toJson(writer: JsonWriter, value: Content?) {
        if (value == null) {
            writer.nullValue()
        } else {
            writer.beginObject()
            writer.name("rendered")
            writer.value(value.rendered)
            writer.endObject()
        }
    }
}

/**
 * Factory para registrar el adaptador de Content
 */
class ContentAdapterFactory : JsonAdapter.Factory {
    override fun create(type: Type, annotations: MutableSet<out Annotation>, moshi: Moshi): JsonAdapter<*>? {
        if (Types.getRawType(type) == Content::class.java) {
            val adapter = ContentAdapter()
            return object : JsonAdapter<Content>() {
                override fun fromJson(reader: JsonReader): Content? = adapter.fromJson(reader)
                override fun toJson(writer: JsonWriter, value: Content?) = adapter.toJson(writer, value)
            }
        }
        return null
    }
}

